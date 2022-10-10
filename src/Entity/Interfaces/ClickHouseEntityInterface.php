<?php

namespace Borislav\Clickhouse\Entity\Interfaces;

interface ClickHouseEntityInterface
{
	/**
	 * @return int|string|null
	 */
	public function getId(): int|string|null;
}
