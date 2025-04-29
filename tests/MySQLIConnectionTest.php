<?php

namespace MarkIngman\Database;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use const MYSQLI_REPORT_OFF;

class MySQLIConnectionTest extends TestCase
{
	protected MySQLIConnection $MySQLIConnection;

	public function setUp(): void
	{
		MySQLIConnection::set_report_mode_strict();
	}

	public function testCreateFailHost(): void
	{
		$config = $this->getConfig();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Could not connect to database \"wrong.hostname\";");

		new MySQLIConnection(
			hostname: 'wrong.hostname',
			username: $config['username'] ?: '',
			password: $config['password'] ?: '',
			database: $config['database'] ?: '',
		);
	}

	public function testCreateFailUser(): void
	{
		$config = $this->getConfig();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Could not connect to database \"127.0.0.1\";");

		new MySQLIConnection(
			hostname: $config['hostname'] ?: '',
			username: 'WRONG-USER',
			password: $config['password'] ?: '',
			database: $config['database'] ?: '',
		);
	}

	public function testCreateFailPass(): void
	{
		$config = $this->getConfig();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Could not connect to database \"127.0.0.1\";");

		new MySQLIConnection(
			hostname: $config['hostname'] ?: '',
			username: $config['username'] ?: '',
			password: 'WRONG-PASS',
			database: $config['database'] ?: '',
		);
	}

	public function testCreateFailDb(): void
	{
		$config = $this->getConfig();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Could not connect to database \"127.0.0.1\";");

		new MySQLIConnection(
			hostname: $config['hostname'] ?: '',
			username: $config['username'] ?: '',
			password: $config['database'] ?: '',
			database: 'WRONG-DB',
		);
	}

	public function testCreate(): void
	{
		$this->assertInstanceOf(MySQLIConnection::class, $this->connectDb());
	}

	public function testGetHostname(): void
	{
		$this->assertEquals('127.0.0.1', $this->connectDb()->get_hostname());
	}

	public function testGetUsername(): void
	{
		$this->assertEquals('user', $this->connectDb()->get_username());
	}

	public function testGetDatabase(): void
	{
		$this->assertEquals('test', $this->connectDb()->get_database());
	}

	public function testReadFailQueryType(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unexpected query type "INSERT"');

		$this->connectDb()->read('INSERT INTO `test_default` SET `a` = 1');
	}

	public function testReadFailQuery(): void
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('Could not prepare query; ');

		$this->connectDb()->read('SELECT * FROM ...');
	}

	public function testReadFailQueryNoReports(): void
	{
		mysqli_report(MYSQLI_REPORT_OFF);

		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('Could not prepare query; ');

		$this->connectDb()->read('SELECT * FROM ...');
	}

	public function testReadSelectSimple(): void
	{
		$this->assertEquals('abc', $this->connectDb()->read(
			'SELECT "abc" AS "value"'
		)->fetch_object()?->value);
	}

	public function testReadSelect(): void
	{
		$db = $this->connectDb();

		$this->assertEquals('Alice', $db->read(
			'SELECT `name` FROM `test_default` WHERE `id` = 101319533431619584'
		)->fetch_object()?->name);

		$this->assertEquals('Alice', $db->read(
			'SELECT `name` FROM `test_default` WHERE `id` = ?', [101319533431619584]
		)->fetch_object()?->name);

		$res = $db->read(
			'SELECT `name` FROM `test_default` ORDER BY `id` LIMIT 3'
		);

		$names = [];
		while ($r = $res->fetch_object()) {
			$names[] = $r->name;
		}

		$this->assertEquals(['Alice', 'Bob', 'Charlie'], $names);
	}

	public function testReadSelectCount(): void
	{
		$this->assertEquals(3, $this->connectDb()->read(
			'SELECT COUNT(*) n FROM `test_default` LIMIT 1000'
		)->fetch_object()?->n);
	}

	public function testReadSelectAll(): void
	{
		$this->assertEquals([
			['id' => 101319533431619584, 'name' => 'Alice'],
			['id' => 101319533431619585, 'name' => 'Bob'],
			['id' => 101319533431619586, 'name' => 'Charlie'],
		], $this->connectDb()->read(
			'SELECT id, name FROM `test_default` LIMIT 3'
		)->fetch_all());
	}

	public function testReadShow(): void
	{
		$this->assertEquals(
			"CREATE TABLE `test_default` (\n" .
			"  `id` bigint(20) unsigned NOT NULL,\n" .
			"  `name` varchar(100) NOT NULL,\n" .
			"  `status` enum('active','inactive','pending') NOT NULL DEFAULT 'pending',\n" .
			"  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),\n" .
			"  UNIQUE KEY `id` (`id`)\n" .
			") ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",
			$this->connectDb()->read('SHOW CREATE TABLE `test_default`')->fetch_object()?->{"Create Table"}
		);
	}

	public function testReadDesc(): void
	{
		$this->assertEquals(
			(object)[
				'Field' => 'name',
				'Type' => 'varchar(100)',
				'Null' => 'NO',
				'Key' => '',
				'Default' => null,
				'Extra' => '',
			],
			$this->connectDb()->read('DESC `test_default` `name`')->fetch_object()
		);
	}

	public function testReadDescribe(): void
	{
		$this->assertEquals(
			'bigint(20) unsigned',
			$this->connectDb()->read('DESCRIBE `test_default` `id`')->fetch_object()?->Type
		);
	}

	public function testReadExplain(): void
	{
		$this->assertEquals(
			(object)[
				'id' => 1,
				'select_type' => 'SIMPLE',
				'table' => 'test_default',
				'type' => 'const',
				'possible_keys' => 'id',
				'key' => 'id',
				'key_len' => '8',
				'ref' => 'const',
				'rows' => '1',
				'Extra' => 'Using index',
			],
			$this->connectDb()->read(
				'EXPLAIN SELECT 1 FROM `test_default` WHERE `id` = 101319533431619584'
			)->fetch_object()
		);
	}

	public function testReadAnalyze(): void
	{
		$db = $this->connectDb();

		$this->assertEquals(
			(object)[
				'Table' => sprintf('%s.test_default', $db->get_database()),
				'Op' => 'analyze',
				'Msg_type' => 'status',
				'Msg_text' => 'OK',
			],
			$db->read(
				'ANALYZE TABLE `test_default`'
			)->fetch_object()
		);
	}

	public function testReadCheck(): void
	{
		$db = $this->connectDb();

		$this->assertEquals(
			(object)[
				'Table' => sprintf('%s.test_default', $db->get_database()),
				'Op' => 'check',
				'Msg_type' => 'status',
				'Msg_text' => 'OK',
			],
			$db->read(
				'CHECK TABLE `test_default`'
			)->fetch_object()
		);
	}

	public function testReadOptimize(): void
	{
		$db = $this->connectDb();

		$this->assertEquals(
			[
				[
					'Table' => sprintf('%s.test_default', $db->get_database()),
					'Op' => 'optimize',
					'Msg_type' => 'note',
					'Msg_text' => 'Table does not support optimize, doing recreate + analyze instead',
				],
				[
					'Table' => sprintf('%s.test_default', $db->get_database()),
					'Op' => 'optimize',
					'Msg_type' => 'status',
					'Msg_text' => 'OK',
				],
			],
			$db->read(
				'OPTIMIZE TABLE `test_default`'
			)->fetch_all()
		);
	}

	public function testReadRepair(): void
	{
		$db = $this->connectDb();

		$this->assertEquals(
			(object)[
				'Table' => sprintf('%s.test_default', $db->get_database()),
				'Op' => 'repair',
				'Msg_type' => 'note',
				'Msg_text' => 'The storage engine for the table doesn\'t support repair',
			],
			$db->read(
				'REPAIR TABLE `test_default`'
			)->fetch_object()
		);
	}

	public function testReadMulti(): void
	{
		$db = $this->connectDb();

		$res = $db->read(
			'SELECT `name` FROM `test_default` ORDER BY `id` LIMIT 3'
		);
		$this->assertEquals(3, $res->num_rows());

		$this->assertEquals('Alice', $res->fetch_object()?->name);

		$this->assertEquals(
			'pending',
			$db->read(
				'SELECT `status` FROM `test_default` WHERE `id` = ?', [101319533431619586]
			)->fetch_object()?->status
		);
		$this->assertEquals('Bob', $res->fetch_object()?->name);

		$this->assertEquals(
			'inactive',
			$db->read(
				'SELECT `status` FROM `test_default` WHERE `id` = ?', [101319533431619585]
			)->fetch_object()?->status
		);

		$this->assertEquals('Charlie', $res->fetch_object()?->name);
		$this->assertNull($res->fetch_object());
		$this->assertEquals(3, $res->num_rows());
	}

	public function testWriteFailQueryType(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unexpected query type "SELECT"');

		$this->connectDb()->write('SELECT 1 FROM `test_default` WHERE `id` = 1000');
	}

	public function testWriteInsert(): void
	{
		$db = $this->connectDb();

		$this->assertTrue(
			$db->write(
				'INSERT INTO `test_default` SET ' .
				'id = ?, ' .
				'name = ?, ' .
				'status = ?, ' .
				'meta = ?',
				[
					101319533431619590,
					'Jane',
					'active',
					'{"test": 1}',
				]
			)
		);

		$res = $db->read(
			'SELECT * FROM `test_default` WHERE `id` = ?', [101319533431619590]
		);

		$this->assertEquals(1, $res->num_rows());
		$this->assertEquals('Jane', $res->fetch_object()?->name);
		$this->assertEquals(1, $db->affected_rows());
	}

	public function testWriteUpdate(): void
	{
		$this->assertTrue(
			$this->connectDb()->write(
				'UPDATE `test_default` SET ' .
				'name = ? ' .
				'WHERE id = ?',
				[
					'Ally',
					101319533431619584,
				]
			)
		);
	}

	public function testWriteDelete(): void
	{
		$db = $this->connectDb();
		$id = $this->getNewId($db);

		$this->assertTrue(
			$db->write(
				'INSERT INTO `test_default` SET ' .
				'id = ?, ' .
				'name = ?',
				[
					$id,
					'Delete',
				]
			)
		);

		$this->assertEquals(
			1,
			$db->read('SELECT COUNT(*) n FROM `test_default` WHERE id = ?', [$id])->fetch_object()?->n
		);

		$this->assertTrue(
			$db->write('DELETE FROM `test_default` WHERE id = ?', [$id])
		);

		$this->assertEquals(
			0,
			$db->read('SELECT COUNT(*) n FROM `test_default` WHERE id = ?', [$id])->fetch_object()?->n
		);
	}

	public function testWriteReplace(): void
	{
		$db = $this->connectDb();
		$id = $this->getNewId($db);

		$this->assertTrue(
			$db->write(
				'INSERT INTO `test_default` SET ' .
				'id = ?, ' .
				'name = ?',
				[
					$id,
					'INSERT',
				]
			)
		);

		$this->assertEquals(
			1,
			$db->read('SELECT COUNT(*) n FROM `test_default` WHERE id = ?', [$id])->fetch_object()?->n
		);

		$this->assertTrue(
			$db->write(
				'REPLACE INTO `test_default` (`id`, `name`) ' .
				'VALUES (?, ?)',
				[$id, 'REPLACE'])
		);

		$this->assertEquals(
			'REPLACE',
			$db->read('SELECT `name` FROM `test_default` WHERE id = ?', [$id])->fetch_object()?->name
		);
	}

	public function testWriteLoad(): void
	{
		$db = $this->connectDb();

		$this->assertTrue(
			$db->write(sprintf(
				'LOAD DATA INFILE "%s" ' .
				'INTO TABLE `test_default` ' .
				'FIELDS TERMINATED BY "," ' .
				'ENCLOSED BY "\\"" ' .
				'LINES TERMINATED BY "\\n" ' .
				'(id, name, status)',
				$db->esc(__DIR__ . '/fixtures/data.csv')
			))
		);

		$this->assertEquals(
			3,
			$db->read(
				'SELECT COUNT(*) n FROM `test_default` WHERE `name` IN (?, ?, ?)',
				['Rod', 'Wang', 'Kelly Anne']
			)->fetch_object()?->n
		);
	}

	public function testCommandFailQueryType(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unexpected query type "SELECT"');

		$this->connectDb()->command('SELECT 1 FROM `test_default` WHERE `id` = 1000');
	}

	public function testCommandCreate(): void
	{
		$db = $this->connectDb();

		$this->assertTrue(
			$db->command(
				'CREATE TABLE `test_create` (' .
				'id BIGINT UNSIGNED NOT NULL UNIQUE' .
				') ENGINE=InnoDB'
			)
		);

		$this->assertEquals(
			'test_create',
			$db->read(
				'SELECT table_name t ' .
				'FROM information_schema.tables ' .
				'WHERE table_schema = ? ' .
				'AND table_name = "test_create"',
				[$db->get_database()]
			)->fetch_object()?->t
		);
	}

	public function testCommandAlter(): void
	{
		$db = $this->connectDb();

		$this->assertEquals(
			(object)[
				'Field' => 'example',
				'Type' => 'varchar(8)',
				'Null' => 'NO',
				'Key' => '',
				'Default' => null,
				'Extra' => '',
			],
			$this->connectDb()->read('DESC `test_alter` `example`')->fetch_object()
		);

		$this->assertTrue(
			$db->command(
				'ALTER TABLE `test_alter` ' .
				'CHANGE COLUMN `example` ' .
				'`example` VARCHAR(32)'
			)
		);

		$this->assertEquals(
			(object)[
				'Field' => 'example',
				'Type' => 'varchar(32)',
				'Null' => 'YES',
				'Key' => '',
				'Default' => null,
				'Extra' => '',
			],
			$this->connectDb()->read('DESC `test_alter` `example`')->fetch_object()
		);
	}

	public function testCommandDrop(): void
	{
		$db = $this->connectDb();

		$this->assertEquals(
			'test_drop',
			$db->read(
				'SELECT table_name t ' .
				'FROM information_schema.tables ' .
				'WHERE table_schema = ? ' .
				'AND table_name = "test_drop"',
				[$db->get_database()]
			)->fetch_object()?->t
		);

		$this->connectDb()->command('DROP TABLE `test_drop`');

		$this->assertNull(
			$db->read(
				'SELECT table_name t ' .
				'FROM information_schema.tables ' .
				'WHERE table_schema = ? ' .
				'AND table_name = "test_drop"',
				[$db->get_database()]
			)->fetch_object()?->t
		);
	}

	public function testCommandTruncate(): void
	{
		$db = $this->connectDb();

		$this->assertEquals(
			3,
			$db->read(
				'SELECT COUNT(*) n FROM `test_truncate`'
			)->fetch_object()?->n
		);

		$this->assertTrue(
			$db->command('TRUNCATE TABLE `test_truncate`')
		);

		$this->assertEquals(
			0,
			$db->read(
				'SELECT COUNT(*) n FROM `test_truncate`'
			)->fetch_object()?->n
		);
	}

	public function testCommandCreateIndex(): void
	{
		$db = $this->connectDb();

		$this->assertNull(
			$db->read(
				'SHOW INDEX FROM test_index'
			)->fetch_object()
		);

		$this->assertTrue(
			$db->command('CREATE INDEX idx_example ON test_index (example)')
		);

		$this->assertEquals(
			(object)[
				'i' => 'idx_example',
				'c' => 'example',
			],
			$db->read(
				'SELECT index_name AS i, column_name AS c ' .
				'FROM information_schema.statistics ' .
				'WHERE table_schema = ? ' .
				'AND table_name = "test_index" ' .
				'AND index_name LIKE "idx_example"',
				[$db->get_database()]
			)->fetch_object()
		);
	}

	public function testCommandDropIndex(): void
	{
		$db = $this->connectDb();

		$this->assertNotNull(
			$db->read(
				'SHOW INDEX FROM test_index_drop'
			)->fetch_object()
		);

		$this->assertTrue(
			$db->command('DROP INDEX idx_example ON test_index_drop')
		);

		$this->assertNull(
			$db->read(
				'SHOW INDEX FROM test_index_drop'
			)->fetch_object()
		);
	}

	public function testCommandSet(): void
	{
		$db = $this->connectDb();

		$this->assertTrue(
			$db->command('SET @test = 123')
		);

		$this->assertEquals(
			123,
			$db->read(
				'SELECT @test AS test'
			)->fetch_object()?->test
		);
	}

	public function testCommandCommit(): void
	{
		$db = $this->connectDb();
		$db2 = $this->connectDb();
		$id1 = $this->getNewId($db);
		$id2 = $this->getNewId($db);

		$this->assertTrue($db->command('BEGIN'));

		$db->write('INSERT INTO `test_default` SET `id` = ?, `name` = "Test 1"', [$id1]);
		$db->write('INSERT INTO `test_default` SET `id` = ?, `name` = "Test 2"', [$id2]);

		$this->assertEquals(
			2,
			$db->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);
		$this->assertEquals(
			0,
			$db2->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);

		$this->assertTrue($db->command('COMMIT'));

		$this->assertEquals(
			2,
			$db->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);
		$this->assertEquals(
			2,
			$db2->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);
	}

	public function testCommandRollback(): void
	{
		$db = $this->connectDb();
		$db2 = $this->connectDb();
		$id1 = $this->getNewId($db);
		$id2 = $this->getNewId($db);

		$this->assertTrue($db->command('BEGIN'));
		$db->write('INSERT INTO `test_default` SET `id` = ?, `name` = "Test 1"', [$id1]);
		$db->write('INSERT INTO `test_default` SET `id` = ?, `name` = "Test 2"', [$id2]);

		$this->assertEquals(
			2,
			$db->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);
		$this->assertEquals(
			0,
			$db2->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);

		$this->assertTrue($db->command('ROLLBACK'));

		$this->assertEquals(
			0,
			$db->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);
		$this->assertEquals(
			0,
			$db2->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);
	}

// GRANT / REVOKE
// FLUSH PRIVILEGES

	public function testTransaction(): void
	{
		$db = $this->connectDb();
		$db2 = $this->connectDb();
		$id1 = $this->getNewId($db);
		$id2 = $this->getNewId($db);

		$db->begin_transaction();
		$db->write('INSERT INTO `test_default` SET `id` = ?, `name` = "Test 1"', [$id1]);
		$db->write('INSERT INTO `test_default` SET `id` = ?, `name` = "Test 2"', [$id2]);

		$this->assertEquals(
			2,
			$db->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);
		$this->assertEquals(
			0,
			$db2->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);

		$db->commit();

		$this->assertEquals(
			2,
			$db->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);
		$this->assertEquals(
			2,
			$db2->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);
	}

	public function testTransactionRollback(): void
	{
		$db = $this->connectDb();
		$db2 = $this->connectDb();
		$id1 = $this->getNewId($db);
		$id2 = $this->getNewId($db);

		$db->begin_transaction();
		$db->write('INSERT INTO `test_default` SET `id` = ?, `name` = "Test 1"', [$id1]);
		$db->write('INSERT INTO `test_default` SET `id` = ?, `name` = "Test 2"', [$id2]);

		$this->assertEquals(
			2,
			$db->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);
		$this->assertEquals(
			0,
			$db2->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);

		$db->rollback();

		$this->assertEquals(
			0,
			$db->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);
		$this->assertEquals(
			0,
			$db2->read(
				'SELECT COUNT(*) n FROM `test_default` ' .
				'WHERE `id` IN (?, ?) ',
				[$id1, $id2]
			)->fetch_object()?->n
		);
	}

	/**
	 * @return array{
	 *     hostname: string,
	 *     username: string,
	 *     password: string,
	 *     database: string,
	 *     socket: string
	 * }
	 */
	protected function getConfig(): array
	{
		if (!$config = parse_ini_file(__DIR__ . '/fixtures/config.ini')) {
			throw new RuntimeException('Could not load config');
		}

		return [
			'hostname' => $config['hostname'] ?? '',
			'username' => $config['username'] ?? '',
			'password' => $config['password'] ?? '',
			'database' => $config['database'] ?? '',
			'socket' => $config['socket'] ?? '',
		];
	}

	protected function connectDb(): MySQLIConnection
	{
		$config = $this->getConfig();

		return new MySQLIConnection(
			hostname: $config['hostname'] ?: null,
			username: $config['username'] ?: null,
			password: $config['password'] ?: null,
			database: $config['database'] ?: null,
		//socket
		);
	}

	protected function getNewId(MySQLIConnection $db): int
	{
		if (!$id = $db->read('SELECT UUID_SHORT() id')->fetch_object()?->id) {
			throw new RuntimeException('Could not create ID');
		}

		return $id;
	}
}
