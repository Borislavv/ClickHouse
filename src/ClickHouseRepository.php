<?php

namespace Borislav\Clickhouse;

use Borislav\Clickhouse\Client\Response\Interfaces\ClickHouseResponseInterface;
use Borislav\Clickhouse\Client\Response\Interfaces\ClickHouseSyncResponseInterface;
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
use Borislav\Clickhouse\Repository\Interfaces\ClickHouseRepositoryInterface;
use LogicException;
use ReflectionClass;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;

class ClickHouseRepository extends AbstractClickHouseRepository implements ClickHouseRepositoryInterface
{
	/**
	 * @method ClickHouseEntityInterface|null find(string|int $id)
	 * @method ClickHouseEntityInterface|null findOneBy(array $criteria)
	 * @method ClickHouseEntityInterface[]    findAll(?array $orderBy = null)
	 * @method ClickHouseEntityInterface[]    findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null)
	 */
    public function __construct(
	    protected NormalizerInterface $normalizer,
	    protected DenormalizerInterface $denormalizer,
		protected string $entityClass
    ) {}
	
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
	public function find(string|int $id): ClickHouseEntityInterface|null
    {
		return $this->findOneBy(['id' => "'".$id."'"]);
    }
	
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
	public function findOneBy(array $criteria): ClickHouseEntityInterface|null
	{
		$result = $this->findBy(criteria: $criteria, limit: 1);
		
		return !empty($result) ? array_shift($result) : null;
	}
	
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
	public function findAll(?array $orderBy = null): array
	{
		return $this->findBy(criteria: [], orderBy: $orderBy);
	}
	
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
    ): array {
	    $result = $this->denormalize(
		    $this->select(
			    'SELECT * FROM {table} {where} {orderBy} {limit} {offset}',
			    array_merge(
				    [
					    'where'     => $this->getWhere($criteria),
					    'orderBy'   => $this->getOrderBy($orderBy),
					    'limit'     => $this->getLimit($limit),
					    'offset'    => $this->getOffset($offset)
				    ],
				    $criteria
			    )
		    )->rows()
	    );
		
		return is_array($result) ? $result : [$result];
    }
	
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
	public function insert(array $items): ClickHouseResponseInterface
	{
		if (empty($items)) {
			throw new ClickHouseBadRequestException(
				'bad request. Attempted to store empty dataset.'
			);
		}

		return parent::insert($this->normalize($items));
	}
	
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
	public function update(array $items, bool $isAwaitableQuery = false): bool
	{
		if (empty($items)) {
			throw new ClickHouseBadRequestException(
				'bad request. Attempted to update by empty dataset.'
			);
		}
		
		$SQLs = $this->buildUpdateSQLs($items);
		
		$statements = [];
		foreach($SQLs as $sql) {
			$statements[] = $this->query(sql: $sql, isAwaitableQuery: $isAwaitableQuery);
		}
		
		$response = true;
		foreach($statements as $key => $statement) {
			if ($statement->isError()) {
				$response = false;
				
				if ($isAwaitableQuery) {
					unset($statements[$key]);
				}
			}
		}
		
		if ($isAwaitableQuery) {
			// await while all queries will be executed
			while(!empty($statements)) {
				/** @var ClickHouseSyncResponseInterface $statement */
				if ($statement = array_shift($statements)) {
					if ($statement->isDone()) continue;
					
					$statements[] = $statement;
					usleep(500000); // 0.5 sec.
				}
			}
		}
		
		return $response;
	}
	
	/**
	 * @param ClickHouseEntityInterface[] $items
	 *
	 * @return string[] An array with SQL update queries.
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
	private function buildUpdateSQLs(array $items): array
	{
		/** @var ClickHouseEntityInterface[] $theSameItems */
		$theSameItems = $this->denormalize(
			$this->select(
				'SELECT * FROM {table} WHERE id IN ({ids})',
				[
					'ids' => implode(
						',',
						array_map(static function(ClickHouseEntityInterface $item) {
							return '\'' . $item->getId() . '\'';
						}, $items)
					)
				]
			)->rows()
		);
		
		$diff = $this->getDiff($items, $theSameItems);
		
		$SQLs = [];
		foreach($diff as $id => $item) {
			$updateSQLs = [];
			foreach($item as $prop => $value) {
				$updateSQLs[] = sprintf('%s=\'%s\'', $prop, $value);
			}
			
			if (!empty($updateSQLs)) {
				$SQLs[] = $this->resolveQueryParams(
					'ALTER TABLE {table} UPDATE {updatePropsSql} WHERE id = :id',
					[
						'updatePropsSql'    => implode(',', $updateSQLs),
						'id'                => $id
					]
				);
			}
		}
		
		return $SQLs;
	}
	
	/**
	 * @param ClickHouseEntityInterface[] $items
	 * @param ClickHouseEntityInterface[] $theSameItems
	 *
	 * @return array
	 *
	 * @throws ClickHouseBadRequestException
	 * @throws ClickHouseMappingException
	 * @throws ClickHouseNormalizationException
	 */
	protected function getDiff(array $items, array $theSameItems): array
	{
		$itemsById = [];
		foreach ($items as $item) {
			$itemsById[$item->getId()] = $item;
		}
		
		$theSameItemsById = [];
		foreach ($theSameItems as $theSameItem) {
			$theSameItemsById[$theSameItem->getId()] = $theSameItem;
		}
		
		$arrItemsById        = $this->normalize($itemsById);
		$arrTheSameItemsById = $this->normalize($theSameItemsById);
		
		foreach(array_keys($arrItemsById) as $id) {
			$arrItemsById[$id] = array_diff_assoc($arrItemsById[$id], $arrTheSameItemsById[$id]);
		}
		
		return $arrItemsById;
	}
	
	/**
	 * @param array $entities
	 *
	 * @return array
	 *
	 * @throws ClickHouseMappingException
	 * @throws ClickHouseBadRequestException
	 * @throws ClickHouseNormalizationException
	 */
	protected function normalize(array $entities): array
	{
		if (empty($entities) || !$entity = current($entities)) {
			throw new ClickHouseBadRequestException(
				'unable to store the empty data set.'
			);
		}

		if (!$entity instanceof ClickHouseEntityInterface) {
			throw new ClickHouseNormalizationException(
				'unable to normalize entity of other type than ClickHouseEntityInterface.'
			);
		}
		
		try {
			return array_map(
				function(ClickHouseEntityInterface $entity) {
					$item = $this->normalizer->normalize($entity);
					
					$normalized = [];
					foreach($this->getColumns($this->entityClass) as $column) {
						if (!array_key_exists($column, $item)) {
							throw new ClickHouseMappingException(
								sprintf(
									'unable to normalize entity `%s` because its property `%s` is not passed in items.',
									(new ReflectionClass($this->entityClass))->getShortName(),
									$column
								)
							);
						}
						$normalized[$column] = $item[$column];
					}
					
					return $normalized;
				},
				$entities
			);
		} catch (Throwable $e) {
			if ($e instanceof ClickHouseMappingException) throw $e;
			
			throw new ClickHouseNormalizationException($e->getMessage());
		}
	}
	
	/**
	 * @param array $items
	 *
	 * @return ClickHouseEntityInterface[]|ClickHouseEntityInterface
	 *
	 * @throws ClickHouseDenormalizationException
	 */
	protected function denormalize(array $items): array|ClickHouseEntityInterface
	{
		// is dataset have not only one single entity?
		$datasetIsNotSingleEntity = true;
		do {
			if ($current = current($items))
				$datasetIsNotSingleEntity &= !is_scalar($current);
			
			if (!$datasetIsNotSingleEntity) break;
		} while(next($items));
		
		try {
			if ($datasetIsNotSingleEntity && count($items) === 1) {
				if (empty($items = array_shift($items)))
					throw new LogicException(
						'failed to retrieve element from incoming dataset.'
					);
				
				// dataset was transformed to single entity array
				$datasetIsNotSingleEntity = false;
			}
			
			return $this->denormalizer
				->denormalize(
					$items,
					$datasetIsNotSingleEntity
						? $this->entityClass . '[]'
						: $this->entityClass
				);
		} catch (ExceptionInterface $e) {
			throw new ClickHouseDenormalizationException(
				message:    $e->getMessage(),
				previous:   $e
			);
		}
	}
	
	/**
	 * @param array $criteria
	 *
	 * @return string
	 */
    private function getWhere(array $criteria): string
    {
	    if (empty($criteria)) return '';
		
		$where = [];
		foreach($criteria as $column => $value) {
			$where[] = sprintf('%1$s = {%1$s}', $column);
		}
		return ' WHERE ' . implode(' AND ', $where);
    }
    
    /**
     * @param array|null $criteria
     *
     * @return string
     */
    private function getOrderBy(?array $criteria): string
    {
        if (empty($criteria)) return '';
        
		$order = [];
		foreach($criteria as $column => $ordering) {
			$order[] = sprintf('%s %s', $column, $ordering);
		}
		return ' ORDER BY ' . implode(', ', $order);
    }
    
    /**
     * @param int|null $limit
     *
     * @return string
     */
    private function getLimit(?int $limit): string
    {
        return is_null($limit)
            ? ''
            : sprintf(' LIMIT %s ', $limit);
    }
    
    /**
     * @param int|null $offset
     *
     * @return string
     */
    private function getOffset(?int $offset): string
    {
        return is_null($offset)
            ? ''
            : sprintf(' OFFSET %s ', $offset);
    }
}
