<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 12/29/14
 * Time: 8:45 PM
 */

namespace src\components\db;

use app\config\Config;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

class DB_Conn
{
    private static $db_conn = NULL;

    public static function getDbConn()
    {
        if (DB_Conn::$db_conn == NULL) {
            $config = new Configuration();
            DB_Conn::$db_conn = DriverManager::getConnection(Config::$config, $config);
            return DB_Conn::$db_conn;
        }
        return DB_Conn::$db_conn;
    }
}

