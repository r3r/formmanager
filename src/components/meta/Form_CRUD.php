<?php
/**
 * Created by PhpStorm.
 * User: Ritesh Reddy
 * Date: 12/29/14
 * Time: 7:29 PM
 */

namespace src\components\meta;

use app\config\Config;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use src\components\db\DB_Conn;
use src\components\utils\JsonIO;

class Form_CRUD extends CRUD
{

    private static function setTableParams()
    {
        CRUD::$_table = Config::$tables['FORM_LIST'];
        CRUD::$_alias = 'FL';
    }

    public static function read($params)
    {
        Form_CRUD::setTableParams();
        return parent::read($params);

    }

    public static function create($params)
    {
        Form_CRUD::setTableParams();
        if (!isset($params['form_elements'])) {
            return JsonIO::emitError("Error! Parameters don't contain form elements");

        }

        if (!isset($params['form_name'])) {
            return JsonIO::emitError("Error! Parameters don't contain form name");
        }

        $db = DB_Conn::getDbConn();
        $db->beginTransaction();
        try {
            $elements = $params['form_elements'];
            foreach ($elements as $key => $element) {
                $elements[$key]['schema'] = JsonIO::receive(Form_ELEMENTS_CRUD::read(array("id" => $element['id'])));
            }

            /*Create all multivalued tables */
            foreach ($elements as $key => $element) {
                if ($element['schema']['multi_valued'] == 1) {
                    if (!isset($element['multi_values'])) {
                        return JsonIO::emitError("Error! MultiValued field doesn't contain values");
                    }
                    $element['foreign_table'] = Config::$tablePrefix['MULTI_VALUED'] . preg_replace('/\s+/', '_', $params['form_name']) . "_" . preg_replace('/\s+/', '_', $element['name']);
                    $elements[$key]['foreign_table'] = $element['foreign_table']; //Foreach loop read-only work around
                    $schema = new Schema();
                    $multi_valued_tbl = $schema->createTable($element['foreign_table']);
                    $multi_valued_tbl->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
                    $multi_valued_tbl->setPrimaryKey(array("id"));
                    $multi_valued_tbl->addColumn("value", "string", array("length" => 255));
                    $queries = $schema->toSql(new MySqlPlatform());
                    $db->executeQuery($queries[0]);


                    foreach ($element['multi_values'] as $value) {
                        $db->insert($element['foreign_table'], array("value" => $value));


                    }

                    if (isset($element['many_to_many']) && $element['many_to_many'] == 1) {
                        $element['join_table'] = Config::$tablePrefix['JOIN_TABLE'] . preg_replace('/\s+/', '_', $params['form_name']) . "_" . preg_replace('/\s+/', '_', $element['name']);
                        $elements[$key]['join_table'] = $element['join_table']; //Foreach loop read-only work around
                        $schema = new Schema();
                        $join_tbl = $schema->createTable($element['join_table']);
                        $join_tbl->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
                        $join_tbl->setPrimaryKey(array("id"));
                        $join_tbl->addColumn("value_id", "integer", array("unsigned" => true));
                        $join_tbl->addColumn("form_instance_id", "integer", array("unsigned" => true));
                        $queries = $schema->toSql(new MySqlPlatform());
                        $db->executeQuery($queries[0]);

                    }
                }
            }


            /* Creating the Form-Data-Instance table*/
            $schema = new Schema();
            $data_instance_tbl = $schema->createTable(Config::$tablePrefix['DATA_INSTANCE'] . preg_replace('/\s+/', '_', $params['form_name']));
            $data_instance_tbl->addColumn("id", "integer", array("unsigned" => true));
            $data_instance_tbl->setPrimaryKey(array("id"));


            foreach ($elements as $element) {
                $options = array();
                if ($element['schema']['type'] == 'text') {
                    if (!isset($element['length'])) {
                        return JsonIO::emitError("Error! Text field doesn't contain length parameter");
                    }
                    $options["length"] = $element['length'];
                }
                $data_instance_tbl->addColumn($element['name'], $element['schema']['db_column_type'], $options);

                //TODO - For multi-valued fields add foreign key constraints
            }
            $queries = $schema->toSql(new MySqlPlatform());
            $db->executeQuery($queries[0]);


            /*Creating the Form-Schema-Instance function*/
            $schema = new Schema();
            $schema_instance_tbl = $schema->createTable(Config::$tablePrefix['SCHEMA_INSTANCE'] . preg_replace('/\s+/', '_', $params['form_name']));
            $schema_instance_tbl->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
            $schema_instance_tbl->setPrimaryKey(array("id"));
            $schema_instance_tbl->addColumn("elementId", "integer", array("unsigned" => true));
            $schema_instance_tbl->addColumn("col_name", "string", array("length" => 255));
            $schema_instance_tbl->addColumn("join_table", "string", array("length" => 255));
            $schema_instance_tbl->addColumn("foreign_table", "string", array("length" => 255));
            $schema_instance_tbl->addColumn("label", "string", array("length" => 255));
            $schema_instance_tbl->addColumn("min", "integer", array("notnull " => false));
            $schema_instance_tbl->addColumn("max", "integer", array("notnull" => false));
            $queries = $schema->toSql(new MySqlPlatform());

            $db->executeQuery($queries[0]);

            /* Insert data into Form-Schema-Instance*/
            foreach ($elements as $element) {
                $values = array();
                $values['elementId'] = $element['schema']['id'];
                $values['col_name'] = preg_replace('/\s+/', '_', $element['name']);
                $values['join_table'] = (isset($element['join_table']) ? $element['join_table'] : "");
                $values['foreign_table'] = (isset($element['foreign_table']) ? $element['foreign_table'] : "");
                $values['label'] = $element['label'];
                $values['min'] = (isset($element['min']) ? $element['min'] : -1);
                $values['max'] = (isset($element['max']) ? $element['max'] : -1);

                $db->insert(Config::$tablePrefix['SCHEMA_INSTANCE'] . preg_replace('/\s+/', '_', $params['form_name']), $values);
            }

            /* Create Form-Views Table */
            $schema = new Schema();
            $view_tbl = $schema->createTable(Config::$tablePrefix['VIEW_TABLE'] . preg_replace('/\s+/', '_', $params['form_name']));
            $view_tbl->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
            $view_tbl->setPrimaryKey(array("id"));
            $view_tbl->addColumn("name", "string", array("length" => 255));
            $view_tbl->addColumn("view_table", "string", array("length" => 255));
            $queries = $schema->toSql(new MySqlPlatform());
            $db->executeQuery($queries[0]);

            /*Add to Form-List Table */
            $db->insert(Config::$tables['FORM_LIST'], array("name" => preg_replace('/\s+/', '_', $params['form_name'])));
            $db->commit();
            return JsonIO::emit("Completed!");

        } catch (\PDOException $e) {
            $db->rollback();
        }


    }

    public static function update($params)
    {

    }

    public static function delete($params)
    {

    }


}