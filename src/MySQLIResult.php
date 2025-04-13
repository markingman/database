<?php

namespace MarkIngman\Database;

use mysqli_result;
use stdClass;

class MySQLIResult implements ResultInterface
{
	public function __construct(
		private readonly ?mysqli_result $result = null,
	) {
	}

	public function fetch_object(): ?stdClass
	{
		return $this->result?->fetch_object() ?: null;
	}

	/** @return array<int, array<string, string|int|float|null>> */
	public function fetch_all(): array
	{
		return $this->result?->fetch_all(MYSQLI_ASSOC) ?? [];
	}

	public function num_rows(): int
	{
		$n = $this->result->num_rows ?? 0;

		// 64-bit assumed; cap at PHP_INT_MAX
		return is_string($n) ? PHP_INT_MAX : $n;
	}
}
