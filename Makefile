DEV-COMPOSE_FILE=docker-compose-dev.yml
ifeq ($(OS),Windows_NT)
PWD := $(shell powershell -Command "[System.IO.Directory]::GetCurrentDirectory()")
else
PWD := $(shell pwd)
endif

up:
	docker compose -f $(DEV-COMPOSE_FILE) up -d

down:
	docker compose -f $(DEV-COMPOSE_FILE) down

clean-db:
	docker compose -f $(DEV-COMPOSE_FILE) down
	docker volume rm klodweb_db_data
	docker compose -f $(DEV-COMPOSE_FILE) up -d

logs:
	docker compose -f $(DEV-COMPOSE_FILE) logs -f

ps:
	docker compose -f $(DEV-COMPOSE_FILE) ps

sh-php:
	docker compose -f $(DEV-COMPOSE_FILE) exec php bash

sh-apache:
	docker compose -f $(DEV-COMPOSE_FILE) exec apache bash

sh-db:
	docker compose -f $(DEV-COMPOSE_FILE) exec db bash

sh-node:
	docker compose -f $(DEV-COMPOSE_FILE) exec node bash

sh-game:
	docker compose -f $(DEV-COMPOSE_FILE) exec game bash

build-tools:
	docker build --no-cache -t node-tools -f DockerfileNode.tools .
	docker build --no-cache -t php-tools -f DockerfilePHP.tools .

lint-js:
	docker run --rm -v "$(PWD):/app/project" node-tools sh -c "npm run lint-all"

fix-js:
	docker run --rm -v "$(PWD):/app/project" node-tools sh -c "npm run lint-all:fix"

lint-php:
	docker run --rm -v "$(PWD):/app" php-tools php-cs-fixer fix . --dry-run --diff --using-cache=no

fix-php:
	docker run --rm -v "$(PWD):/app" php-tools php-cs-fixer fix . --using-cache=no

fix-all:
	make fix-js && make fix-php