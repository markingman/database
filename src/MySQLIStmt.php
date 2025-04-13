<?php

namespace MarkIngman\Database;

use mysqli_stmt;

class MySQLIStmt implements StmtInterface
{
	public function __construct(
		private readonly ?mysqli_stmt $stmt = null,
	) {
	}

	/** @param array<int, string|int|float|null>|null $params */
	public function execute(?array $params = null): bool
	{
		return $this->stmt?->execute($params) ?? false;
	}

	public function get_result(): MySQLIResult
	{
		$res = $this->stmt?->get_result() ?: null;

		return new MySQLIResult($res);
	}

	public function get_affected_rows(): int
	{
		$n = $this->stmt?->affected_rows ?? 0;

		// 64-bit assumed; cap at PHP_INT_MAX
		return is_string($n) ? PHP_INT_MAX : $n;
	}
}
