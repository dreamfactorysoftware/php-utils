<?php namespace DreamFactory\Library\Utility;

/**
 * Down-and-dirty PDO SQL class
 */
class Sql
{
    //*************************************************************************
    //* Constants
    //*************************************************************************

    /**
     * @var string MySql server pattern
     */
    const DSN_TEMPLATE_MYSQL = 'mysql:host=%%host_name%%;dbname=%%db_name%%';
    /**
     * @var string Postgres server pattern
     */
    const DSN_TEMPLATE_PGSQL = 'pgsql:host=%%host_name%%';

    //*************************************************************************
    //* Members
    //*************************************************************************

    /**
     * @var \PDO
     */
    protected static $_connection = null;
    /**
     * @var \PDOStatement
     */
    protected static $_statement = null;
    /**
     * @var string
     */
    protected static $_connectionString = null;

    //*************************************************************************
    //* Methods
    //*************************************************************************

    /**
     * Creates and returns an optionally parameter-bound \PDOStatement object
     *
     * @param string $sql
     * @param \PDO   $connection
     * @param int    $fetchMode Set to false to not touch fetch mode
     *
     * @return \PDOStatement
     */
    public static function createStatement($sql, &$connection = null, $fetchMode = \PDO::FETCH_ASSOC)
    {
        $_db = static::_checkConnection($connection);

        /** @var $_statement \PDOStatement */
        $_statement = $_db->prepare($sql);

        if (false !== $fetchMode) {
            $_statement->setFetchMode($fetchMode);
        }

        return $_statement;
    }

    /**
     * Creates and returns an optionally parameter-bound \PDOStatement object suitable for iteration
     *
     * @param string $sql
     * @param array  $parameters
     * @param \PDO   $connection
     * @param int    $fetchMode Set to false to not touch fetch mode
     *
     * @return DataReader
     */
    public static function query($sql, $parameters = null, &$connection = null, $fetchMode = \PDO::FETCH_ASSOC)
    {
        return DataReader::create($sql, $parameters, $connection, $fetchMode);
    }

    /**
     * Executes a SQL statement
     *
     * @param string $sql
     * @param array  $parameters
     * @param \PDO   $connection
     * @param int    $fetchMode
     *
     * @return int The number of affected rows
     */
    public static function execute($sql, $parameters = null, $connection = null, $fetchMode = \PDO::FETCH_ASSOC)
    {
        static::$_statement = static::createStatement($sql, $connection, $fetchMode);

        if (empty($parameters)) {
            return static::$_statement->execute();
        }

        return static::$_statement->execute($parameters);
    }

    /**
     * Executes a SQL query
     *
     * @param string $sql
     * @param array  $parameters
     * @param \PDO   $connection
     * @param int    $fetchMode
     *
     * @return null|array
     */
    public static function find($sql, $parameters = null, $connection = null, $fetchMode = \PDO::FETCH_ASSOC)
    {
        if (false === ($_reader = static::query($sql, $parameters, $connection, $fetchMode))) {
            return null;
        }

        return $_reader->fetch();
    }

    /**
     * Executes the given sql statement and returns all results
     *
     * @param string $sql
     * @param array  $parameters
     * @param \PDO   $connection
     * @param int    $fetchMode
     *
     * @return array|bool
     */
    public static function findAll($sql, $parameters = null, $connection = null, $fetchMode = \PDO::FETCH_ASSOC)
    {
        if (false === ($_reader = static::query($sql, $parameters, $connection, $fetchMode))) {
            return null;
        }

        return $_reader->fetchAll();
    }

    /**
     * Returns the first column of the first row or null
     *
     * @param string $sql
     * @param int    $columnNumber
     * @param array  $parameters
     * @param \PDO   $connection
     *
     * @param int    $fetchMode
     *
     * @return mixed
     */
    public static function scalar($sql, $columnNumber = 0, $parameters = null, $connection = null, $fetchMode = \PDO::FETCH_ASSOC)
    {
        if (false === ($_reader = static::query($sql, $parameters, $connection, $fetchMode))) {
            return null;
        }

        return $_reader->fetchColumn($columnNumber);
    }

    /**
     * @static
     *
     * @param array $parameters
     * @param \PDO  $connection
     *
     * @throws \LogicException
     * @return \PDO
     */
    protected static function _checkConnection(&$parameters = [], &$connection = null)
    {
        //	Allow laziness
        if ($parameters instanceof \PDO) {
            $connection = $parameters;
            $parameters = [];
        }

        static::setConnection($_db = $connection ?: static::$_connection);

        //	Connect etc...
        if (empty($_db) || !(($_db instanceof \PDO) && $_db->getAttribute(\PDO::ATTR_CONNECTION_STATUS))) {
            throw new \LogicException('Cannot proceed until a database connection has been established. Try setting the "connection" property.');
        }

        return $_db;
    }

    /**
     * @param \PDO|null $connection
     */
    public static function setConnection($connection = null)
    {
        if (null !== static::$_connection) {
            static::$_connection = null;
        }

        static::$_connection = $connection;
    }

    /**
     * @return \PDO
     */
    public static function getConnection()
    {
        return static::$_connection;
    }

    /**
     * @param string $connectionString
     * @param string $userName
     * @param string $password
     * @param array  $pdoOptions
     *
     * @return void|\PDO
     */
    public static function setConnectionString($connectionString, $userName = null, $password = null, array $pdoOptions = [])
    {
        static::setConnection(null);

        //	If you set a connection string a new connection one is created for you
        if (!empty($connectionString)) {
            static::$_connectionString = $connectionString;

            static::$_connection =
                new \PDO(static::$_connectionString,
                    $userName,
                    $password,
                    array_merge([\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,], $pdoOptions));
        }

        return static::$_connection;
    }

    /**
     * @return string
     */
    public static function getConnectionString()
    {
        return static::$_connectionString;
    }

    /**
     * @param \PDOStatement $statement
     */
    public static function setStatement($statement)
    {
        static::$_statement = $statement;
    }

    /**
     * @return \PDOStatement
     */
    public static function getStatement()
    {
        return static::$_statement;
    }
}
