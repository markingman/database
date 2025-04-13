<?php

namespace MarkIngman\Database;

use stdClass;

interface ResultInterface
{
	public function fetch_object(): ?stdClass;

	/** @return array<int, array<string, string|int|float|null>> */
	public function fetch_all(): array;

	public function num_rows(): int;
}
