# Makefile for local development

.DEFAULT_GOAL := help
.PHONY: help
NAME=database-test

help:
	@grep -E '^[a-zA-Z0-9._-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ": ## "}; {printf "\033[36m%-28s\033[0m %s\n", $$1, $$2}' | sed 's/Makefile://g'

build8.3: ## Build a PHP 8.3 Docker image for local development
	@docker build --build-arg PHP_VERSION=8.3 -t $(NAME) .

build8.4: ## Build a PHP 8.4 Docker image for local development
	@docker build --build-arg PHP_VERSION=8.4 -t $(NAME) .

db: ## Run MariaDb console in container
	@docker run -it --rm $(NAME) mariadb -uroot -proot

test: ## Run tests
	@docker run -it --rm -v `pwd`/src:/usr/src/app/src -v `pwd`/tests:/usr/src/app/tests -v `pwd`/phpunit-coverage:/usr/src/app/phpunit-coverage $(NAME) vendor/bin/phpunit

analyse: ## Run analyse
	@docker run -it --rm -v `pwd`/src:/usr/src/app/src -v `pwd`/tests:/usr/src/app/tests $(NAME) vendor/bin/phpstan analyse -c phpstan.neon
