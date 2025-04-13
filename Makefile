# Makefile for local development

.DEFAULT_GOAL := help
.PHONY: help
NAME=templator-test

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ": ## "}; {printf "\033[36m%-28s\033[0m %s\n", $$1, $$2}' | sed 's/Makefile://g'

build: ## Build a Docker image for local development
	@docker build -t $(NAME) .

# setup: ## Run Composer install in container
# 	@docker run -it --rm -v `pwd`/src:/usr/src/app/src -v `pwd`/tests:/usr/src/app/tests -v `pwd`/phpunit-coverage:/usr/src/app/phpunit-coverage $(NAME) /usr/bin/composer install

db: ## Run MariaDb console in container
	@docker run -it --rm $(NAME) mariadb -uroot -proot

test: ## Run container for tests
	@docker run -it --rm -v `pwd`/src:/usr/src/app/src -v `pwd`/tests:/usr/src/app/tests -v `pwd`/phpunit-coverage:/usr/src/app/phpunit-coverage $(NAME) vendor/bin/phpunit

analyse: ## Run container for analyse
	@docker run -it --rm -v `pwd`/src:/usr/src/app/src -v `pwd`/tests:/usr/src/app/tests $(NAME) vendor/bin/phpstan analyse -c phpstan.neon
