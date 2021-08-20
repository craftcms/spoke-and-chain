DUMPFILE?=seed.sql
EXEC?=docker-compose exec console
RUN?=docker-compose run --rm console

.PHONY: update

update:
	cp .env.docker .env
	${RUN} php craft db/restore ${DUMPFILE}
	docker compose up -d
	${EXEC} composer update
	${EXEC} php craft migrate/all
	${EXEC} php craft project-config/apply --force
	${EXEC} php craft queue/run
	${EXEC} php craft gc --delete-all-trashed
	${EXEC} php craft db/backup ${DUMPFILE} --overwrite
	git add ${DUMPFILE} composer.lock config/project
	git commit -m "Update Composer & seed data."

seed:
	${EXEC} console php craft demos/seed
	${EXEC} console php craft users/create --admin
