<?php

namespace Borislav\Clickhouse\Traits\Interfaces;

use Borislav\Clickhouse\Client\Adapter\Interfaces\ClickHouseClientAdapterInterface;
use Borislav\Clickhouse\Exception\ClickHouseNotImplementedException;
use Borislav\Clickhouse\Exception\ClickHouseReflectionException;

interface ClickHouseClientTraitInterface
{
	/**
	 * @return ClickHouseClientAdapterInterface
	 *
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 */
	public function clickhouse(): ClickHouseClientAdapterInterface;
}
