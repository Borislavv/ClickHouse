<?php

namespace Borislav\Clickhouse\Client\Response;

use Borislav\Clickhouse\Client\Response\Interfaces\ClickHouseSyncResponseInterface;
use Borislav\Clickhouse\Exception\ClickHouseConfigNotFoundException;
use Borislav\Clickhouse\Exception\ClickHouseNotImplementedException;
use Borislav\Clickhouse\Exception\ClickHouseReflectionException;
use Borislav\Clickhouse\Exception\ClickHouseTransportException;
use Borislav\Clickhouse\Traits\ClickHouseClientTrait;

class ClickHouseSyncResponse extends ClickHouseResponse implements ClickHouseSyncResponseInterface
{
	public const TIMEOUT_PER_REQUEST_IN_MILLISECONDS = 500;  // 0.5 sec.
	public const DEFAULT_TIMEOUT_IN_SECONDS          = 600;  // 10 min.
	
	use ClickHouseClientTrait;
	
	private string|null $queryUniqueKey = null;
	
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
	public function await(?int $timeoutSeconds = null): bool
	{
		$thresholdTimeout = time() + ($timeoutSeconds ?: self::DEFAULT_TIMEOUT_IN_SECONDS);
		
		do {
			if (!$this->isDone()) {
				usleep(self::TIMEOUT_PER_REQUEST_IN_MILLISECONDS);
				continue;
			}
			return true;
		} while (time() < $thresholdTimeout);
		return false;
	}
	
	/**
	 * @return bool
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 * @throws ClickHouseTransportException
	 */
	public function isDone(): bool
	{
		return (bool) $this
			->clickhouse()
			->select(
				'SELECT is_done FROM system.mutations WHERE command LIKE :queryUniqueKey LIMIT 1',
				['queryUniqueKey' => '%' . $this->queryUniqueKey . '%']
			)
			->fetchOne('is_done');
	}
	
	/**
	 * @param string $queryUniq
	 *
	 * @return $this
	 */
	public function setQueryUniqueKey(string $queryUniq): self
	{
		$this->queryUniqueKey = $queryUniq;
		
		return $this;
	}
}
