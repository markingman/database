<?php

namespace MarkIngman\Database;

use InvalidArgumentException;
use LogicException;
use mysqli;
use mysqli_sql_exception;
use RuntimeException;
use Throwable;
use function in_array;
use function is_string;
use function mysqli_report;
use function sprintf;
use function strtok;
use function strtoupper;
use function substr;
use function trim;
use const MYSQLI_REPORT_ERROR;
use const MYSQLI_REPORT_STRICT;
use const PHP_INT_MAX;

class MySQLIConnection implements DbConnectionInterface
{
	const array TYPES_READ = ['SELECT', 'SHOW', 'DESC', 'DESCRIBE', 'EXPLAIN', 'ANALYZE', 'CHECK', 'OPTIMIZE', 'REPAIR'];
	const array TYPES_WRITE = ['INSERT', 'UPDATE', 'DELETE', 'REPLACE', 'LOAD'];
	const array TYPES_COMMAND = ['ALTER', 'BEGIN', 'COMMIT', 'CREATE', 'DROP', 'FLUSH', 'GRANT', 'ROLLBACK', 'SET', 'START', 'TRUNCATE'];

	protected mysqli $Db;
	private ?StmtInterface $stmt = null;

	public function __construct(
		public ?string $hostname = null,
		public ?string $username = null,
		?string $password = null,
		public ?string $database = null,
		?int $port = null,
		?string $socket = null
	) {
		try {
			$this->Db = @new mysqli($hostname, $username, $password, $database, $port, $socket);
		} catch (Throwable $e) {
			throw new RuntimeException(sprintf(
				'Could not connect to database "%s"; %s',
				$hostname ?? '?', $e->getMessage()
			));
		}
	}

	public static function set_report_mode_strict(): void
	{
		// Note this is PHP 8.1+ default, strict throws exceptions. Set in php.ini in production
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	}

	public function get_hostname(): string
	{
		return $this->hostname ?? '';
	}

	public function get_username(): string
	{
		return $this->username ?? '';
	}

	public function get_database(): string
	{
		return $this->database ?? '';
	}

	public function esc(string $str): string
	{
		return $this->Db->real_escape_string($str);
	}

	/** @param array<int, string|int|float|null>|null $vars */
	public function read(string $query, ?array $vars = null): ResultInterface
	{
		if (!in_array(strtoupper($this->get_query_token($query)), static::TYPES_READ, true)) {
			throw new InvalidArgumentException(sprintf('Unexpected query type "%s"', $this->get_query_token($query)));
		}

		$this->stmt = $this->prepare($query);
		$this->stmt->execute($vars);

		return $this->stmt->get_result();
	}

	/** @param array<int, string|int|float|null>|null $vars */
	public function write(string $query, ?array $vars = null): bool
	{
		if (!in_array(strtoupper($this->get_query_token($query)), static::TYPES_WRITE, true)) {
			throw new InvalidArgumentException(sprintf('Unexpected query type "%s"', $this->get_query_token($query)));
		}

		$this->stmt = $this->prepare($query);

		return $this->stmt->execute($vars);
	}

	/** @param array<int, string|int|float|null>|null $vars */
	public function command(string $query, ?array $vars = null): bool
	{
		if (!in_array(strtoupper($this->get_query_token($query)), static::TYPES_COMMAND, true)) {
			throw new InvalidArgumentException(sprintf('Unexpected query type "%s"', $this->get_query_token($query)));
		}

		$this->stmt = $this->prepare($query);

		return $this->stmt->execute($vars);
	}

	public function prepare(string $query): StmtInterface
	{
		try {
			$stmt = $this->Db->prepare($query);
		} catch (mysqli_sql_exception $e) {
			throw new LogicException(message: 'Could not prepare query; ' . $e->getMessage(), previous: $e);
		}

		if ($stmt === false) {
			throw new LogicException(sprintf(
				'Could not prepare query; %s',
				$this->Db->error ?? 'Unknown error; ' . substr(trim($query), 0, 32) . '...'
			));
		}

		return new MySQLIStmt($stmt);
	}

	public function affected_rows(): int
	{
		return $this->stmt ? $this->stmt->get_affected_rows() : 0;
	}

	public function begin_transaction(): void
	{
		$this->Db->begin_transaction();
	}

	public function commit(): void
	{
		$this->Db->commit();
	}

	public function rollback(): void
	{
		$this->Db->rollback();
	}

	public function insert_id(): int
	{
		$id = $this->Db->insert_id;

		// 64-bit assumed; cap at PHP_INT_MAX
		return is_string($id) ? PHP_INT_MAX : $id;
	}

	protected function get_query_token(string $query): string
	{
		return (string)strtok($query, ' ');
	}
}
