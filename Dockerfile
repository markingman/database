ARG PHP_VERSION=8.3
ARG COMPOSER_VERSION=2.9.2
ARG XDEBUG_VERSION=3.4.7

FROM php:${PHP_VERSION}-cli AS base
ARG XDEBUG_VERSION

RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    set -eux; \
    apt-get update; \
	apt-get install -y --no-install-recommends \
		unzip \
		default-mysql-server \
		default-mysql-client \
		libmariadb-dev \
		mariadb-server \
		mariadb-client; \
	rm -rf /var/lib/apt/lists/*; \
	docker-php-ext-install mysqli; \
	pecl install -o xdebug-${XDEBUG_VERSION}; \
	docker-php-ext-enable xdebug; \
	pecl clear-cache && rm -rf /tmp/pear; \
    mkdir -p /var/run/mysqld && chown -R mysql:mysql /var/run/mysqld;

FROM composer:${COMPOSER_VERSION} AS composer

FROM base

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /usr/src/app
COPY . .

RUN /usr/bin/composer install --prefer-dist

COPY my.cnf /etc/mysql/my.cnf

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
