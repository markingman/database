<?php

namespace MarkIngman\Database;

interface DbConnectionInterface
{
	public function __construct(
		?string $hostname = null,
		?string $username = null,
		?string $password = null,
		?string $database = null,
		?int $port = null,
		?string $socket = null,
	);

	public function get_hostname(): string;

	public function get_username(): string;

	public function get_database(): string;

	/** @param array<int, string|int|float|null>|null $vars */
	public function read(string $query, ?array $vars = null): ResultInterface;

	/** @param array<int, string|int|float|null>|null $vars */
	public function write(string $query, ?array $vars = null): bool;

	/** @param array<int, string|int|float|null>|null $vars */
	public function command(string $query, ?array $vars = null): bool;

	public function prepare(string $query): StmtInterface;

	public function affected_rows(): int;

	public function begin_transaction(): void;

	public function commit(): void;

	public function rollback(): void;

	public function esc(string $str): string;
}
