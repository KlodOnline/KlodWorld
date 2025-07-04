DEV-COMPOSE_FILE=docker-compose-dev.yml

up:
	@npm install --prefix ./chat || exit 0
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

.PHONY: tools-build tools-lint

tools-build:
	docker build --no-cache -t node-tools -f Dockerfile.tools .

lint-www:
	docker run --rm -v .:/app/project -w /app node-tools sh -c "npm run lint-www"

lint-chat:
	docker run --rm -v .:/app/project -w /app node-tools sh -c "npm run lint-chat"
