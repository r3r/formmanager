<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 1/1/15
 * Time: 2:21 PM
 */

namespace src\components\utils;

use app\config\Config;

class Namer
{
    public static function getDataInstanceTblName($form_name)
    {
        $prefix = Config::$tablePrefix['DATA_INSTANCE'];
        return $prefix . self::sanitize($form_name);
    }

    public static function getSchemaInstanceTblName($form_name)
    {
        $prefix = Config::$tablePrefix['SCHEMA_INSTANCE'];
        return $prefix . self::sanitize($form_name);
    }

    public static function getMultiValuedTblName($form_name, $col_name)
    {
        $prefix = Config::$tablePrefix['MULTI_VALUED'];
        return $prefix . self::sanitize($form_name) . "_" . self::sanitize($col_name);
    }

    public static function getJoinTblName($form_name, $col_name)
    {
        $prefix = Config::$tablePrefix['JOIN_TABLE'];
        return $prefix . self::sanitize($form_name) . "_" . self::sanitize($col_name);
    }

    public static function getViewTblName($form_name)
    {
        $prefix = Config::$tablePrefix['VIEW_TABLE'];
        return $prefix . self::sanitize($form_name);
    }

    public static function sanitize($name)
    {
        $name = preg_replace('/\s+/', '_', $name); //remove spaces
        $name = preg_replace('/[^a-zA-Z0-9]+/', '_', $name); //replace all non alphanumerics with a _
        $name = strtolower($name);
        return $name;
    }

} 