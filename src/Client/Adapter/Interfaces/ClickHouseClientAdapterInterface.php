<?php

namespace Borislav\Clickhouse\Client\Adapter\Interfaces;

use Borislav\Clickhouse\Client\Response\Interfaces\ClickHouseResponseInterface;
use Borislav\Clickhouse\Client\Response\Interfaces\ClickHouseSyncResponseInterface;
use Borislav\Clickhouse\Exception\ClickHouseBadRequestException;
use Borislav\Clickhouse\Exception\ClickHouseConfigNotFoundException;
use Borislav\Clickhouse\Exception\ClickHouseTransportException;

interface ClickHouseClientAdapterInterface
{
	/**
	 * @param string $sql
	 * @param array  $params
	 *
	 * @return ClickHouseResponseInterface
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseTransportException
	 */
	public function select(string $sql, array $params = []): ClickHouseResponseInterface;
	
	/**
	 * @param string     $table
	 * @param array      $values
	 * @param array|null $columns
	 *
	 * @return ClickHouseResponseInterface
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseTransportException
	 */
	public function insert(string $table, array $values, array|null $columns = null): ClickHouseResponseInterface;
	
	/**
	 * If $isAwaitableQuery === false, then @see ClickHouseResponseInterface
	 *                                 else @see ClickHouseSyncResponseInterface
	 *
	 * @param string $sql
	 * @param array  $params
	 * @param bool   $isAwaitableQuery
	 *
	 * @return ClickHouseResponseInterface|ClickHouseSyncResponseInterface
	 *
	 * @throws ClickHouseBadRequestException
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseTransportException
	 */
	public function query(
		string $sql,
		array $params = [],
		bool $isAwaitableQuery = false
	): ClickHouseResponseInterface|ClickHouseSyncResponseInterface;
	
	/**
	 * @return bool
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseTransportException
	 */
	public function ping(): bool;
}
