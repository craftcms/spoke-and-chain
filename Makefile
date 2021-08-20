DUMPFILE?=seed.sql
COMPOSE?=docker-compose
EXEC?=${COMPOSE} exec console
RUN?=${COMPOSE} run --rm console

.PHONY: update

update:
	cp .env.docker .env
	${RUN} php craft db/restore ${DUMPFILE}
	${COMPOSE} up -d
	${EXEC} composer update
	${EXEC} php craft migrate/all
	${EXEC} php craft project-config/apply --force
	${EXEC} php craft queue/run
	${EXEC} php craft gc --delete-all-trashed
	${EXEC} php craft db/backup ${DUMPFILE} --overwrite

seed:
	${EXEC} console php craft demos/seed
	${EXEC} console php craft users/create --admin
