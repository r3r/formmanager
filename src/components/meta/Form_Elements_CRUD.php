<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 12/30/14
 * Time: 2:00 PM
 */

namespace src\components\meta;

use app\config\Config;

class Form_Elements_CRUD extends CRUD
{
    public static function read($params)
    {
        CRUD::$_table = Config::$tables['FORM_ELEMENTS'];
        CRUD::$_alias = 'FE';
        return parent::read($params);
    }

    public static function create($params)
    {


    }

    public static function update($params)
    {

    }

    public static function delete($params)
    {

    }
} 