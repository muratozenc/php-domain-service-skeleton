.PHONY: up down migrate seed test lint stan fix clean

up:
	docker-compose up -d
	@echo "Waiting for services to be ready..."
	@sleep 5
	@echo "Services are up!"

down:
	docker-compose down

migrate:
	docker-compose exec app php bin/migrate.php

seed:
	docker-compose exec app php bin/seed.php

test:
	docker-compose exec app composer test

lint:
	docker-compose exec app composer lint

fix:
	docker-compose exec app composer fix

stan:
	docker-compose exec app composer stan

clean:
	docker-compose down -v
	rm -rf vendor/

install:
	docker-compose exec app composer install

