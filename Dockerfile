FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
	zip \
	default-mysql-server \
	default-mysql-client \
	libmariadb-dev \
	mariadb-server \
	mariadb-client 

RUN docker-php-ext-install mysqli \
    && pecl install xdebug-3.3.2 \
    && docker-php-ext-enable xdebug
    
RUN mkdir -p /var/run/mysqld && chown -R mysql:mysql /var/run/mysqld

COPY my.cnf /etc/mysql/my.cnf

COPY --from=composer:2.8.1 /usr/bin/composer /usr/bin/composer

COPY . /usr/src/app
WORKDIR /usr/src/app
RUN /usr/bin/composer install

ENV XDEBUG_MODE=coverage

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
