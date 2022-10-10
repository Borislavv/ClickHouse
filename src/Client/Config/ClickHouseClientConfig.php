<?php

namespace Borislav\Clickhouse\Client\Config;

use Borislav\Clickhouse\Exception\ClickHouseConfigNotFoundException;

class ClickHouseClientConfig
{
    /** Env. keys for extract values */
    protected const HOST        = 'CLICKHOUSE_HOST';
    protected const PORT        = 'CLICKHOUSE_PORT';
    protected const USERNAME    = 'CLICKHOUSE_USERNAME';
    protected const PASSWORD    = 'CLICKHOUSE_PASSWORD';
    protected const DATABASE    = 'CLICKHOUSE_DATABASE';
	
	private string              $host;
	private string|int          $port;
    private string              $username;
    private string              $password;
    private string              $database;
    
    private static self|null    $config = null;
    
    /**
     * @return self
     *
     * @throws ClickHouseConfigNotFoundException
     */
    public static function getConfig(): self
    {
        if (!is_null(self::$config)) {
            return self::$config;
        }
        
        self::$config = (new ClickHouseClientConfig())
            ->init();
        
        return self::$config;
    }
	
	/**
	 * @return string[]
	 */
    public function getConnectParams(): array
    {
        return [
			'host'      => $this->host,
	        'port'      => $this->port,
	        'username'  => $this->username,
	        'password'  => $this->password
        ];
    }
	
	/**
	 * @return string[]
	 */
	public function getSettings(): array
	{
		return [
			'database' => $this->database
		];
	}

    /**
     * @throws ClickHouseConfigNotFoundException
     */
    private function init(): self
    {
        foreach (
            [
                self::HOST,
                self::PORT,
                self::USERNAME,
                self::PASSWORD,
                self::DATABASE
            ] as $param
        ) {
            if (is_null($_ENV[$param] ?? null)) {
                throw new ClickHouseConfigNotFoundException(
                    sprintf(
                        '%s parameter has been omitted in configuration.',
                        $param
                    )
                );
            }
        }
        
        $this->host     = $_ENV[self::HOST];
		$this->port     = $_ENV[self::PORT];
        $this->username = $_ENV[self::USERNAME];
        $this->password = $_ENV[self::PASSWORD];
        $this->database = $_ENV[self::DATABASE];
        
        return $this;
    }
    
    private function __construct() {}
    private function __clone() {}
}
