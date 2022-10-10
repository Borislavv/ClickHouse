<?php

namespace Borislav\Clickhouse\Client\Response\Interfaces;

use Borislav\Clickhouse\Exception\ClickHouseTransportException;

interface ClickHouseResponseInterface
{
	/**
	 * @return string
	 *
	 * @throws ClickHouseTransportException
	 */
	public function rawData(): string;
	
	/**
	 * @param mixed $key
	 *
	 * @return mixed
	 *
	 * @throws ClickHouseTransportException
	 */
	public function fetchRow(mixed $key = null): mixed;
	
	/**
	 * @param mixed $key
	 *
	 * @return mixed
	 *
	 * @throws ClickHouseTransportException
	 */
	public function fetchOne(mixed $key = null): mixed;
	
	/**
	 * @param mixed $path
	 *
	 * @return array
	 *
	 * @throws ClickHouseTransportException
	 */
	public function rowsAsTree(mixed $path): array;
	
	/**
	 * @return array
	 *
	 * @throws ClickHouseTransportException
	 */
	public function rows(): array;
	
	/**
	 * @return bool
	 *
	 * @throws ClickHouseTransportException
	 */
	public function error(): bool;
	
	/**
	 * @return bool
	 *
	 * @throws ClickHouseTransportException
	 */
	public function isError(): bool;
	
	/**
	 * Return a prepared sql for request.
	 *
	 * @return string
	 */
	public function sql(): string;
	
	/**
	 * Return a bool value which indicate the instance of current class is awaitable or not.
	 *
	 * @return bool
	 */
	public function isSyncable(): bool;
}
