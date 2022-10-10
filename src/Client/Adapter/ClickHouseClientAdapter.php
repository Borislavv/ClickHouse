<?php

namespace Borislav\Clickhouse\Client\Adapter;

use Borislav\Clickhouse\Client\Adapter\Interfaces\ClickHouseClientAdapterInterface;
use Borislav\Clickhouse\Client\Config\ClickHouseClientConfig;
use Borislav\Clickhouse\Exception\ClickHouseBadRequestException;
use Borislav\Clickhouse\Exception\ClickHouseConfigNotFoundException;
use Borislav\Clickhouse\Exception\ClickHouseInstanceIsUnserializeableException;
use Borislav\Clickhouse\Exception\ClickHouseTransportException;
use Borislav\Clickhouse\Traits\ClickHouseClientTrait;
use Borislav\Clickhouse\Traits\Interfaces\ClickHouseClientTraitInterface;
use Borislav\Clickhouse\Client\Response\ClickHouseResponse;
use Borislav\Clickhouse\Client\Response\ClickHouseSyncResponse;
use Borislav\Clickhouse\Client\Response\Interfaces\ClickHouseResponseInterface;
use Borislav\Clickhouse\Client\Response\Interfaces\ClickHouseSyncResponseInterface;
use ClickHouseDB\Client;
use ClickHouseDB\Exception\TransportException;
use ClickHouseDB\Statement;
use Ramsey\Uuid\Uuid;

/**
 * This class must be used from suitable trait.
 * @see ClickHouseClientTrait
 * @see ClickHouseClientTraitInterface
 */
class ClickHouseClientAdapter implements ClickHouseClientAdapterInterface
{
	private static Client|null $client = null;
	
	/**
	 * @return ClickHouseClientAdapter
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseTransportException
	 */
	public function clickhouse(): ClickHouseClientAdapterInterface
	{
		self::initClient();
		
		return $this;
	}
	
	/**
	 * @param string $sql
	 * @param array  $params
	 *
	 * @return ClickHouseResponseInterface
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseTransportException
	 */
	public function select(string $sql, array $params = []): ClickHouseResponseInterface
	{
		return $this->respond(self::getClient()->select($sql, $params));
	}
	
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
	public function insert(string $table, array $values, array|null $columns = null): ClickHouseResponseInterface
	{
		try {
			return $this->respond(self::getClient()->insert($table, $values, $columns));
		} catch (TransportException $e) {
			throw new ClickHouseTransportException(
				message: $e->getMessage(), previous: $e
			);
		}
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
	 * @throws ClickHouseTransportException
	 */
	public function query(
		string $sql,
		array $params = [],
		bool $isAwaitableQuery = false
	): ClickHouseResponseInterface|ClickHouseSyncResponseInterface {
		try {
			if ($isAwaitableQuery) {
				[$queryUniq, $sql] = $this->makeQueryAwaitable($sql);
			}
			
			$response = $this->respond(
				self::getClient()->write($sql, $params),
				$isAwaitableQuery
			);
			
			if ($response->isSyncable() && $isAwaitableQuery) {
				$response /** @var ClickHouseSyncResponse $response */
					->setQueryUniqueKey($queryUniq);
			}
			
			return $response;
		} catch (TransportException $e) {
			throw new ClickHouseTransportException(
				message: $e->getMessage(), previous: $e
			);
		}
	}
	
	/**
	 * @param string $sql
	 *
	 * @see ClickHouseResponseInterface
	 * @see ClickHouseSyncResponseInterface
	 *
	 * @return string[] Return a unique query key and awaitable-sql
	 * Usage -> unpack it: [uniq, awaitableSql] = $this->makeQueryAwaitable(sql).
	 *
	 * @throws ClickHouseBadRequestException
	 */
	private function makeQueryAwaitable(string $sql): array
	{
		if (!is_int(stripos($sql, ' WHERE '))) {
			throw new ClickHouseBadRequestException(
				'queries without `WHERE` condition is not awaitable, try to use sync. ClickHouseResponse instead ClickHouseSyncResponse.'
			);
		} else {
			$queryUniqueKey = Uuid::uuid4();
			$sql = str_ireplace(' WHERE ', sprintf(' WHERE (\'%1$s\' = \'%1$s\') AND ', $queryUniqueKey), $sql);
		}
		
		return [$queryUniqueKey, $sql];
	}
	
	/**
	 * @return bool
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseTransportException
	 */
	public function ping(): bool
	{
		return self::getClient()->ping();
	}
	
	/**
	 * @return array
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseTransportException
	 */
	public function showTables(): array
	{
		return self::getClient()->showTables();
	}
	
	/**
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseTransportException
	 */
	private static function initClient(): void
	{
		if (!is_null(self::$client)) {
			return;
		}
		
		$cfg = ClickHouseClientConfig::getConfig();
		
		self::$client = new Client(
			$cfg->getConnectParams(),
			$cfg->getSettings()
		);
		
		if (!self::$client->ping()) {
			throw new ClickHouseTransportException(
				'ping database failed.'
			);
		}
	}
	
	/**
	 * @return Client
	 *
	 * @throws ClickHouseConfigNotFoundException
	 * @throws ClickHouseTransportException
	 */
	private function getClient(): Client
	{
		if (!is_null(self::$client)) {
			return self::$client;
		}
		
		self::initClient();
		
		return self::$client;
	}
	
	/**
	 * @param Statement $statement
	 * @param bool      $isAwaitableQuery
	 *
	 * @return ClickHouseResponseInterface|ClickHouseSyncResponseInterface
	 */
	private function respond(
		Statement $statement,
		bool $isAwaitableQuery = false
	): ClickHouseResponseInterface|ClickHouseSyncResponseInterface {
		return $isAwaitableQuery
			? (new ClickHouseSyncResponse($statement->getRequest()))
			: (new ClickHouseResponse($statement->getRequest()));
	}
	
	/**
	 * @throws ClickHouseInstanceIsUnserializeableException
	 */
	public function __wakeup() { throw new ClickHouseInstanceIsUnserializeableException(); }
	private function __construct() {}
	private function __clone() {}
}
