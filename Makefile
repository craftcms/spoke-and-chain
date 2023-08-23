DUMPFILE ?= seed.sql
COMPOSE ?= docker-compose
EXEC ?= ${COMPOSE} exec -T web
EXEC_MYSQL ?= ${COMPOSE} exec -T mysql mysql --user=craft --password=secret --database=craft --execute
RUN ?= ${COMPOSE} run --rm web
WEB_CONTAINER = docker-compose ps -q web

.PHONY: init update restore backup seed clean test gc

init:
	cp .env.docker .env
	${COMPOSE} up -d
	${EXEC} composer install
update:
	${EXEC} composer update --no-interaction
	${EXEC} php craft up --interactive=0
	${EXEC} php craft queue/run --interactive=0
restore:
	${EXEC} php craft db/restore ${DUMPFILE}
backup:
	${EXEC} php craft db/backup ${DUMPFILE} --overwrite --interactive=0
	docker cp $(shell ${WEB_CONTAINER}):/app/composer.lock ./
	docker cp $(shell ${WEB_CONTAINER}):/app/seed.sql ./
	docker cp $(shell ${WEB_CONTAINER}):/app/config/project ./config/
seed:
	${EXEC} php craft demos/seed
clean:
	${EXEC} php craft demos/seed/clean
test:
	${EXEC} curl -IX GET --fail http://localhost:8080/actions/app/health-check
	${EXEC} curl -IX GET --fail http://localhost:8080/
gc:
	${EXEC} php craft gc --delete-all-trashed --interactive=0
	${EXEC} php craft utils/prune-revisions --max-revisions=1
	${EXEC_MYSQL} 'TRUNCATE TABLE `searchindex`'
	${EXEC} php craft resave/assets --update-search-index=1
	${EXEC} php craft resave/carts --update-search-index=1
	${EXEC} php craft resave/categories --update-search-index=1
	${EXEC} php craft resave/entries --update-search-index=1
	${EXEC} php craft resave/orders --update-search-index=1
	${EXEC} php craft resave/products --update-search-index=1
	${EXEC} php craft resave/tags --update-search-index=1
	${EXEC} php craft resave/users --update-search-index=1
update_and_reseed: init restore update clean seed gc backup
