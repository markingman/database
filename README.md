# Database Connection

Lightweight database connection for PHP applications.

## Status

This is a small utility library shared for convenience. Maintenance is best-effort and may be minimal.

This repository is published for use and reference. External contributions are not currently being accepted.

## Installation

Install via Composer:

```bash
composer require markingman/database
```

## Usage Overview

Open a connection:

```php
$db = new MySQLIConnection($host, $user, $pass, $db);
```

Shorthand simple read queries:

```php
$name = $db->read('SELECT `name` FROM `test_default` WHERE `id` = 123')->fetch_object()?->name;
```

Interate results:

```php
$res = $db->read('SELECT `name` FROM `test_default` ORDER BY `id` LIMIT 3');

$names = [];
while ($r = $res->fetch_object()) {
	$names[] = $r->name;
}

if ($res->num_rows() === 3) {
	echo 'There are 3 results';
}
```

Write and use variables:

```php
$db->write(
	'INSERT INTO `test_default` SET ' .
	'id = ?, name = ?, status = ?, meta = ?',
	[123, 'Jane', 'active', '{"test": 1}', ]
);

if ($db->affected_rows() === 1) {
	echo 'Record created'
}
```

Perform commands:

```php
$db->command('CREATE INDEX idx_example ON test_index (example)');
```

See `MySQLIConnectionTest` for more examples.

## License

This project is licensed under the MIT License.
