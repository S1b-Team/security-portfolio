# One-command lab. Requires Docker with the compose plugin.
.PHONY: up test down

up:
	docker compose up -d --build app-fixed app-vulnerable mock-metadata

test:
	docker compose --profile test run --rm tests

down:
	docker compose --profile test down -v
