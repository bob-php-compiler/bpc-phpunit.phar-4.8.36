<?php

abstract class PHPUnit_DbUnit_Mysql_TestCase extends PHPUnit_Extensions_Database_TestCase
{
   protected static $connection;
    protected static $pdo;

    public function getConnection()
    {
        if (self::$connection == null) {
            $config = $this->getPDOConfig();
            self::$pdo = new PDO($config['dsn'], $config['username'], $config['passwd']);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection = $this->createDefaultDBConnection(self::$pdo, $config['dbname']);
        }
        return self::$connection;
    }

    protected function getPDOConfig()
    {
        $mysqlHost     = '127.0.0.1';
        $mysqlPort     = 3307;
        $mysqlDbname   = 'our_phpunit_test';
        $mysqlCharset  = 'utf8';
        $mysqlUsername = 'root';
        $mysqlPasswd   = '123456';

        return array(
            'dsn'      => sprintf(
                            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                            $mysqlHost,
                            $mysqlPort,
                            $mysqlDbname,
                            $mysqlCharset
                          ),
            'username' => $mysqlUsername,
            'passwd'   => $mysqlPasswd,
            'dbname'   => $mysqlDbname
        );
    }
}
