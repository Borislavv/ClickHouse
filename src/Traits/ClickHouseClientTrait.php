<?php

namespace Borislav\Clickhouse\Traits;

use Borislav\Clickhouse\ClickHouseRepository;
use Borislav\Clickhouse\Client\Adapter\ClickHouseClientAdapter;
use Borislav\Clickhouse\Client\Adapter\Interfaces\ClickHouseClientAdapterInterface;
use Borislav\Clickhouse\Exception\ClickHouseNotImplementedException;
use Borislav\Clickhouse\Exception\ClickHouseReflectionException;
use Borislav\Clickhouse\Traits\Interfaces\ClickHouseClientTraitInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Throwable;

trait ClickHouseClientTrait
{
	private static ClickHouseClientAdapterInterface|null $clickhouse = null;
	
	/**
	 * @return ClickHouseClientAdapterInterface
	 *
	 * @throws ClickHouseNotImplementedException
	 * @throws ClickHouseReflectionException
	 */
	public function clickhouse(): ClickHouseClientAdapterInterface
	{
		if (!$this instanceof ClickHouseClientTraitInterface) {
			throw new ClickHouseNotImplementedException(
				'cannot get ClickHouse client because the ClickHouseClientTraitInterface is not implemented.'
			);
		}
		
		if (!is_null(self::$clickhouse)) return self::$clickhouse;
		
		$reflectedClickHouseClientAdapter = new ReflectionClass(
			ClickHouseClientAdapter::class
		);
		
		$clientMethod = null;
		foreach( /** @var ReflectionMethod $method */
			$reflectedClickHouseClientAdapter
				->getMethods() as $method
		) {
			try {
				if (!$method->hasReturnType()) continue;
				
				if (is_string($name = $method->getReturnType()?->getName())) {
					if (strcasecmp($name, ClickHouseClientAdapterInterface::class) === 0) {
						$clientMethod = $method;
					}
				}
			} catch (Throwable) {
				continue;
			}
		}
		
		if (is_null($clientMethod)) {
			throw new ClickHouseReflectionException(
				'unable to continue execution because the ClickHouse method, which provide a client was not found.'
			);
		}
		
		$constructor = $reflectedClickHouseClientAdapter
			->getConstructor();
		
		if (is_null($constructor)) {
			throw new ClickHouseReflectionException(
				'unable to continue execution because cannot create instance of ClickHouseRepository. Internal error.'
			);
		}
		
		$constructor->setAccessible(true);
		$clientMethod->setAccessible(true);
		
		try {
			/** @var ClickHouseRepository $objectOfClickHouseRepository */
			$objectOfClickHouseClientAdapter = $reflectedClickHouseClientAdapter
				->newInstanceWithoutConstructor();
			
			$constructor->invoke($objectOfClickHouseClientAdapter);
			
			self::$clickhouse = $clientMethod->invoke($objectOfClickHouseClientAdapter);
			
			return self::$clickhouse;
		} catch (ReflectionException $e) {
			throw new ClickHouseReflectionException(
				$e->getMessage()
			);
		}
	}
}
