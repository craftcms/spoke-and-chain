DUMPFILE ?= seed.sql
COMPOSE ?= docker-compose
EXEC ?= ${COMPOSE} exec -T web
RUN ?= ${COMPOSE} run --rm web
WEB_CONTAINER = docker-compose ps -q web

.PHONY: update restore backup seed test

update:
	cp .env.docker .env
	${COMPOSE} up -d
	make restore
	${EXEC} composer update --no-interaction
	${EXEC} php craft migrate/all --interactive=0
	${EXEC} php craft project-config/apply --force --interactive=0
	${EXEC} php craft queue/run --interactive=0
	${EXEC} php craft gc --delete-all-trashed --interactive=0
	make backup
restore:
	${EXEC} php craft db/restore ${DUMPFILE}
backup:
	${EXEC} php craft db/backup ${DUMPFILE} --overwrite --interactive=0
	docker cp $(shell ${WEB_CONTAINER}):/app/composer.lock ./
	docker cp $(shell ${WEB_CONTAINER}):/app/seed.sql ./
	docker cp $(shell ${WEB_CONTAINER}):/app/config/project ./config/

seed:
	${EXEC} php craft demos/seed
test: seed
	${EXEC} curl -IX GET --fail http://localhost:8080/actions/app/health-check
	${EXEC} curl -IX GET --fail http://localhost:8080/
