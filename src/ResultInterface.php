<?php

namespace MarkIngman\Database;

use stdClass;

interface ResultInterface
{
	public function fetch_object(): ?stdClass;

	/** @return array<string, string> */
	public function fetch_all(): array;

	public function num_rows(): int;
}
