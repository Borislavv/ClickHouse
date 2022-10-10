<?php

namespace Borislav\Clickhouse\Client\Response;

use Borislav\Clickhouse\Client\Response\Interfaces\ClickHouseResponseInterface;
use Borislav\Clickhouse\Client\Response\Interfaces\ClickHouseSyncResponseInterface;
use Borislav\Clickhouse\Exception\ClickHouseTransportException;
use ClickHouseDB\Exception\TransportException;
use ClickHouseDB\Statement;

class ClickHouseResponse extends Statement implements ClickHouseResponseInterface
{
	/**
	 * @return string
	 *
	 * @throws ClickHouseTransportException
	 */
	public function rawData(): string
	{
		return $this->execMethod(
			function() {
				return parent::rawData();
			}
		);
	}
	
	/**
	 * @param mixed|null $key
	 *
	 * @return mixed
	 *
	 * @throws ClickHouseTransportException
	 */
	public function fetchRow(mixed $key = null): mixed
	{
		return $this->execMethod(
			function(mixed $key = null) {
				return parent::fetchRow($key);
			},
			[$key]
		);
	}
	
	/**
	 * @param mixed|null $key
	 *
	 * @return mixed
	 *
	 * @throws ClickHouseTransportException
	 */
	public function fetchOne(mixed $key = null): mixed
	{
		return $this->execMethod(
			function(mixed $key = null) {
				return parent::fetchOne($key);
			},
			[$key]
		);
	}
	
	/**
	 * @param mixed $path
	 *
	 * @return array
	 *
	 * @throws ClickHouseTransportException
	 */
	public function rowsAsTree(mixed $path): array
	{
		return $this->execMethod(
			function(mixed $path) {
				return parent::rowsAsTree($path);
			},
			[$path]
		);
	}
	
	/**
	 * @return array
	 *
	 * @throws ClickHouseTransportException
	 */
	public function rows(): array
	{
		return $this->execMethod(
			function() {
				return parent::rows();
			}
		);
	}
	
	/**
	 * @return bool
	 *
	 * @throws ClickHouseTransportException
	 */
	public function error(): bool
	{
		return $this->execMethod(
			function() {
				return parent::error();
			}
		);
	}
	
	/**
	 * @return bool
	 *
	 * @throws ClickHouseTransportException
	 */
	public function isError(): bool
	{
		return $this->execMethod(
			function() {
				return parent::isError();
			}
		);
	}
	
	/**
	 * @return string
	 *
	 * @throws ClickHouseTransportException
	 */
	public function sql(): string
	{
		return $this->execMethod(
			function() {
				return parent::sql();
			}
		);
	}
	
	/**
	 * Return a bool value which indicate the instance of current class is awaitable or not.
	 *
	 * @return bool
	 */
	public function isSyncable(): bool
	{
		return $this instanceof ClickHouseSyncResponseInterface;
	}
	
	/***
	 * @param callable $method
	 * @param array    $args this array will be unpacked, be careful
	 *
	 * @return mixed
	 *
	 * @throws ClickHouseTransportException
	 */
	private function execMethod(callable $method, array $args = []): mixed
	{
		try {
			return !empty($args)
				? $method(...$args)
				: $method();
		} catch (TransportException $e) {
			throw new ClickHouseTransportException(message: $e->getMessage(), previous: $e);
		}
	}
}
