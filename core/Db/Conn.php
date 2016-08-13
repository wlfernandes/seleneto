<?php

namespace Core\Db;

abstract class Conn
{
    private static $instance;

    protected static function getConn()
    {
        if (!self::$instance) {
            try {
                $options = [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8",
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_CLASS
                ];
                self::$instance = new \PDO(DB_TYPE . ":host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
            } catch (\PDOException $e) {
                echo $e->getMessage();
            }
        }
        return self::$instance;
    }
}