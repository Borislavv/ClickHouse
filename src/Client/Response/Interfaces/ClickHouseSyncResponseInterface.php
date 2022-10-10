<?php

namespace Borislav\Clickhouse\Client\Response\Interfaces;

use Borislav\Clickhouse\Exception\ClickHouseConfigNotFoundException;
use Borislav\Clickhouse\Exception\ClickHouseNotImplementedException;
use Borislav\Clickhouse\Exception\ClickHouseReflectionException;
use Borislav\Clickhouse\Exception\ClickHouseTransportException;
use Borislav\Clickhouse\Traits\Interfaces\ClickHouseClientTraitInterface;

/**
 * This is awaitable query response, extended from simple statement by allow u to wait
 *      a fixation of mutations like ALTER TABLE {table} UPDATE|DELETE.
 *
 * Simple usage:
 *      $this
 *          ->clickhouse()
 *          ->query('ALTER TABLE {table} UPDATE created_at toDateTime(now())')
 *          ->await(); <- do it for stop further execution and waiting
 *
 * Check it out yourself:
 *      $syncState = $this
 *          ->clickhouse()
 *          ->query('ALTER TABLE {table} UPDATE created_at toDateTime(now())');
 *
 *      $syncState->isDone() <- use it for single check
 *          ? return true
 *          : throw new Exception('request has not been completed yet);
 */
interface ClickHouseSyncResponseInterface extends ClickHouseResponseInterface, ClickHouseClientTraitInterface
{
	/**
	 * @param int|null $timeoutSeconds Duration of await in microseconds,
	 *                                  if it's null, then await long as necessary.
	 *
	 * @return bool
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 * @throws ClickHouseTransportException
	 */
	public function await(?int $timeoutSeconds = null): bool;
	
	/**
	 * @return bool
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 * @throws ClickHouseTransportException
	 */
	public function isDone(): bool;
}
