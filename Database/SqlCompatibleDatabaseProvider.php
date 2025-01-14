<?php

namespace Database;

use PDO;
use Core\Database\DatabaseInterface;
use Core\Env;

abstract class SqlCompatibleDatabaseProvider implements DatabaseInterface
{
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    protected $database;
    protected $connection;

    public function __construct()
    {
        $this->host = Env::get()->HOST;
        $this->username = Env::get()->USERNAME;
        $this->password = Env::get()->PASSWORD;
        $this->database = Env::get()->DATABASE;
        $this->port = Env::get()->PORT;

    }

    public function connect()
    {
        $dsn = $this->getDsn();
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Add additional connection options here if needed
        ];

        /**
         * Note: If you encourter an Error relating to Prefix You can opt for a non-database mode by following these steps:
         * - Disable the database-related lines inside Bootstrap/Database.php.
         * - Modify the PrefixManager to use JSON instead of the database in Bootstrap/Prefix.php.
         */
        $this->connection = new PDO($dsn, $this->username, $this->password, $options);
    }

    abstract protected function getDsn();

    public function query($sql)
    {
        return $this->connection->query($sql);
    }

    public function close()
    {
        unset($this->connection);
    }
}
