<?php

namespace Borislav\Clickhouse\Repository\Interfaces;

use Borislav\Clickhouse\Client\Response\Interfaces\ClickHouseResponseInterface;
use Borislav\Clickhouse\Entity\Interfaces\ClickHouseEntityInterface;
use Borislav\Clickhouse\Exception\ClickHouseBadRequestException;
use Borislav\Clickhouse\Exception\ClickHouseConfigNotFoundException;
use Borislav\Clickhouse\Exception\ClickHouseDenormalizationException;
use Borislav\Clickhouse\Exception\ClickHouseEntityNotFoundException;
use Borislav\Clickhouse\Exception\ClickHouseMappingException;
use Borislav\Clickhouse\Exception\ClickHouseNormalizationException;
use Borislav\Clickhouse\Exception\ClickHouseNotImplementedException;
use Borislav\Clickhouse\Exception\ClickHouseReflectionException;
use Borislav\Clickhouse\Exception\ClickHouseTransportException;

interface ClickHouseRepositoryInterface
{
	/**
	 * @param string|int $id
	 *
	 * @return ClickHouseEntityInterface|null
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseDenormalizationException
	 * @throws ClickHouseEntityNotFoundException
	 * @throws ClickHouseTransportException
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 */
	public function find(string|int $id): ClickHouseEntityInterface|null;
	
	/**
	 * @param array      $criteria
	 * @param array|null $orderBy
	 * @param int|null   $limit
	 * @param int|null   $offset
	 *
	 * @return ClickHouseEntityInterface[]
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseDenormalizationException
	 * @throws ClickHouseEntityNotFoundException
	 * @throws ClickHouseTransportException
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 */
	public function findBy(
		array $criteria,
		?array $orderBy = null,
		?int $limit = null,
		?int $offset = null
	): array;
	
	/**
	 * @param array $criteria
	 *
	 * @return ClickHouseEntityInterface|null
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseDenormalizationException
	 * @throws ClickHouseEntityNotFoundException
	 * @throws ClickHouseTransportException
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 */
	public function findOneBy(array $criteria): ClickHouseEntityInterface|null;
	
	/**
	 * @param array|null $orderBy
	 *
	 * @return ClickHouseEntityInterface[]
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseDenormalizationException
	 * @throws ClickHouseEntityNotFoundException
	 * @throws ClickHouseTransportException
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 */
	public function findAll(?array $orderBy = null): array;
	
	/**
	 * @param ClickHouseEntityInterface[] $items
	 *
	 * @return ClickHouseResponseInterface
	 *
	 * @throws ClickHouseBadRequestException
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseEntityNotFoundException
	 * @throws ClickHouseMappingException
	 * @throws ClickHouseNormalizationException
	 * @throws ClickHouseTransportException
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 */
	public function insert(array $items): ClickHouseResponseInterface;
	
	/**
	 * @param ClickHouseEntityInterface[]   $items
	 * @param bool                          $isAwaitableQuery
	 *                                      @see ClickHouseResponseInterface
	 *                                      @see ClickHouseSyncResponseInterface
	 *
	 * @return bool
	 *
	 * @throws ClickHouseBadRequestException
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseDenormalizationException
	 * @throws ClickHouseEntityNotFoundException
	 * @throws ClickHouseMappingException
	 * @throws ClickHouseNormalizationException
	 * @throws ClickHouseTransportException
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 */
	public function update(array $items, bool $isAwaitableQuery = false): bool;
}
