<?php

namespace Borislav\Clickhouse;

use Borislav\Clickhouse\Client\Response\Interfaces\ClickHouseResponseInterface;
use Borislav\Clickhouse\Client\Response\Interfaces\ClickHouseSyncResponseInterface;
use Borislav\Clickhouse\Entity\Interfaces\ClickHouseEntityInterface;
use Borislav\Clickhouse\Exception\ClickHouseBadRequestException;
use Borislav\Clickhouse\Exception\ClickHouseConfigNotFoundException;
use Borislav\Clickhouse\Exception\ClickHouseEntityNotFoundException;
use Borislav\Clickhouse\Exception\ClickHouseInstanceIsUnserializeableException;
use Borislav\Clickhouse\Exception\ClickHouseNotImplementedException;
use Borislav\Clickhouse\Exception\ClickHouseReflectionException;
use Borislav\Clickhouse\Exception\ClickHouseTransportException;
use Borislav\Clickhouse\Traits\ClickHouseClientTrait;
use Borislav\Clickhouse\Traits\Interfaces\ClickHouseClientTraitInterface;
use ReflectionClass;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;

abstract class AbstractClickHouseRepository implements ClickHouseClientTraitInterface
{
	use ClickHouseClientTrait;
	
	/**
	 * @param string $sql
	 * @param array  $params
	 *
	 * @return ClickHouseResponseInterface
	 *
	 * @throws ClickHouseBadRequestException
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseEntityNotFoundException
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 * @throws ClickHouseTransportException
	 */
	public function select(string $sql, array $params = []): ClickHouseResponseInterface
	{
		return $this->clickhouse()->select($this->resolveQueryParams($sql, $params));
	}
	
	/**
	 * @param ClickHouseEntityInterface[] $items
	 *
	 * @return ClickHouseResponseInterface
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseEntityNotFoundException
	 * @throws ClickHouseTransportException
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 */
	public function insert(array $items): ClickHouseResponseInterface
	{
		return $this->clickhouse()->insert($this->getTableName(), $items, $this->getColumns($this->entityClass));
	}
	
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
	 * @throws ClickHouseEntityNotFoundException
	 * @throws ClickHouseTransportException
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 */
	public function query(
		string $sql,
		array $params = [],
		bool $isAwaitableQuery = false
	): ClickHouseResponseInterface|ClickHouseSyncResponseInterface {
		return $this->clickhouse()->query($this->resolveQueryParams($sql, $params), [], $isAwaitableQuery);
	}
	
	/**
	 * @return string
	 *
	 * @throws ClickHouseEntityNotFoundException
	 */
	protected function getTableName(): string
	{
		try {
			return strtolower(
				(new ReflectionClass($this->entityClass))
					->getShortName()
			);
		} catch (Throwable) {
			throw new ClickHouseEntityNotFoundException(
				sprintf('target class `%s` not found.', $this->entityClass)
			);
		}
	}
	
	/**
	 * @param ClickHouseEntityInterface|string $target
	 *
	 * @return array
	 *
	 * @throws ClickHouseEntityNotFoundException
	 */
	protected function getColumns(ClickHouseEntityInterface|string $target): array
	{
		try {
			$props = (new ReflectionClass($target))
				->getProperties();
		} catch (Throwable) {
			throw new ClickHouseEntityNotFoundException(
				sprintf('target class `%s` not found.', $target)
			);
		}
		
		return array_map(
			static function ($item) {
				return $item->getName();
			},
			$props
		);
	}
	
	/**
	 * @param string $sql
	 * @param array  $params
	 *
	 * @return string
	 *
	 * @throws ClickHouseBadRequestException
	 * @throws ClickHouseEntityNotFoundException
	 */
	protected function resolveQueryParams(string $sql, array $params = []): string
	{
		if (empty($params)) return $sql;
		
		if (!in_array($this->getTableName(), $params)) $params = array_merge(
			['table' => $this->getTableName()], $params
		);
		
		foreach($params as $alias => $value) {
			$withQuotes     = ':' . $alias;
			$withoutQuotes  = '{' . $alias . '}';
			
			if (is_array($value)) $value = implode(',', $value);
			
			if (!is_scalar($value)) throw new ClickHouseBadRequestException(
				sprintf('Parameter must be a scalar or array type, `%s` passed.', gettype($value))
			);
			
			if (is_int(stripos($sql, $withQuotes))) {
				$sql = str_ireplace($withQuotes, '\'' . $value . '\'', $sql);
			} elseif(is_int(stripos($sql, $withoutQuotes))) {
				$sql = str_ireplace($withoutQuotes, $value, $sql);
			}
		}
		
		return $sql;
	}
    
    /**
     * @throws ClickHouseInstanceIsUnserializeableException
     */
    public function __wakeup() { throw new ClickHouseInstanceIsUnserializeableException(); }
	
	/**
	 * @param NormalizerInterface   $normalizer
	 * @param DenormalizerInterface $denormalizer
	 * @param string                $entityClass
	 */
    abstract protected function __construct(NormalizerInterface $normalizer, DenormalizerInterface $denormalizer, string $entityClass);
	
    private function __clone() {}
}
