<?php
namespace app\config;


class Config
{
    public static $config = array(
        'dbname' => 'form_manager',
        'user' => 'root',
        'password' => 'root',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    );

    public static $tablePrefix = array(
        'DATA_INSTANCE' => 'Data_',
        'SCHEMA_INSTANCE' => 'Schema_',
        'MULTI_VALUED' => 'Multi_Value_',
        'JOIN_TABLE' => 'Join_',
        'VIEW_TABLE' => 'View_'
    );

    public static $tables = array(
        'FORM_LIST' => 'form_list',
        'FORM_ELEMENTS' => 'form_elements'

    );
}

