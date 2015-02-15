<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 1/1/15
 * Time: 2:18 PM
 */

namespace src\components\meta;


class Schema_CRUD extends CRUD
{
    private static function setTableParams($table)
    {
        CRUD::$_table = $table;
        CRUD::$_alias = 'SC';
    }

    public static function read($params)
    {
        Schema_CRUD::setTableParams($params['table_name']);
        unset($params['table_name']);
        return parent::read($params);
    }

    public static function public_filter($schema)
    {

        $privateFields = ["elementId",

            "options",
            "join_table",
            "foreign_table"];

        foreach ($privateFields as $privateField) {
            if (isset($schema[$privateField])) {
                unset($schema[$privateField]);
            }
        }


        return $schema;


    }

} 