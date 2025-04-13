<?php

require_once __DIR__ . '/../vendor/autoload.php';

// TODO: copy ./fixtures to /tmp, set dynamic database name

exec('mariadb -uroot -proot < ' . __DIR__ . '/fixtures/init.sql');
exec('mariadb -uroot -proot test < ' . __DIR__ . '/fixtures/schema.sql');

