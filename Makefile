DUMPFILE ?= storage/backups/seed.sql
COMPOSE ?= docker-compose
EXEC ?= ${COMPOSE} exec -T console
RUN ?= ${COMPOSE} run --rm console

.PHONY: update restore backup seed

update:
	cp .env.docker .env
	make restore
	${COMPOSE} up -d	
	${EXEC} composer update --no-interaction
	${EXEC} php craft migrate/all --interactive=0
	${EXEC} php craft project-config/apply --force --interactive=0
	${EXEC} php craft queue/run --interactive=0
	${EXEC} php craft gc --delete-all-trashed --interactive=0
	make backup
restore:
	${RUN} php craft db/restore ${DUMPFILE}
backup:
	${RUN} php craft db/backup ${DUMPFILE} --overwrite --interactive=0
seed:
	${EXEC} console php craft demos/seed
	${EXEC} console php craft users/create --admin
