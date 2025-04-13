<?php

namespace MarkIngman\Database;

interface StmtInterface
{
	/** @param array<int, string|int|float|null>|null $params */
	public function execute(?array $params = null): bool;

// 	TODO: public function close(): void;

	public function get_result(): ResultInterface;

	public function get_affected_rows(): int;
}
