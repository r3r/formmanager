<?php
/**
 * Created by PhpStorm.
 * User: Ritesh Reddy
 * Date: 12/29/14
 * Time: 7:29 PM
 */

namespace src\components\meta;

use app\config\Config;
use app\includes\JsonIO;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use src\components\db\DB_Conn;
use src\components\utils\Namer;

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
                if ($elements[$key]['schema'] === array()) {
                    return JsonIO::emitError("Invalid Form Element. Id " . $element['id']);
                }
            }

            /*Add to Form-List Table */
            try {
                $db->insert(Config::$tables['FORM_LIST'], array("name" => preg_replace('/\s+/', '_', $params['form_name'])));
            } catch (\Exception $e) {
                $db->rollback();
                $err = array(1 => -1, 2 => "Unknown details");
                foreach ($e->getTrace()[0]['args'] as $eElement) {
                    if ($eElement instanceof PDOException) {
                        $err = $eElement->errorInfo;
                        break;
                    }
                }
                return JsonIO::emitError("SQL Exception when inserting into table " . Config::$tables['FORM_LIST'], $err[1], $err[2]);

            }

            /*Create all multivalued tables */
            foreach ($elements as $key => $element) {
                if ($element['schema']['multi_valued'] == 1) {
                    if (!isset($element['multi_values'])) {
                        return JsonIO::emitError("Error! MultiValued field doesn't contain values");
                    }
                    $element['foreign_table'] = Namer::getMultiValuedTblName($params['form_name'], $element['name']);
                    $elements[$key]['foreign_table'] = $element['foreign_table']; //Foreach loop read-only work around
                    $schema = new Schema();
                    $multi_valued_tbl = $schema->createTable($element['foreign_table']);
                    $multi_valued_tbl->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
                    $multi_valued_tbl->setPrimaryKey(array("id"));
                    $multi_valued_tbl->addColumn("value", "string", array("length" => 255, "PlatformOptions" => array("unique" => true))); //unique doesn't work with dbal

                    $queries = $schema->toSql(new MySqlPlatform());
                    $queries[1] = "ALTER TABLE  " . $element['foreign_table'] . " ADD UNIQUE (`value`);"; //Custom MySQL
                    try {
                        $db->query($queries[0]);
                        $db->query($queries[1]);
                    } catch (\Exception $e) {
                        $db->rollback();
                        $err = array(1 => -1, 2 => "Unknown details");
                        foreach ($e->getTrace()[0]['args'] as $eElement) {
                            if ($eElement instanceof PDOException) {
                                $err = $eElement->errorInfo;
                                break;
                            }
                        }
                        return JsonIO::emitError("SQL Exception when creating table " . $multi_valued_tbl->getName(), $err[1], $err[2]);
                    }

                    foreach ($element['multi_values'] as $value) {
                        try {
                            $db->insert($element['foreign_table'], array("value" => $value));
                        } catch (\Exception $e) {
                            $db->rollback();
                            $err = array(1 => -1, 2 => "Unknown details");
                            foreach ($e->getTrace()[0]['args'] as $eElement) {
                                if ($eElement instanceof PDOException) {
                                    $err = $eElement->errorInfo;
                                    break;
                                }
                            }
                            return JsonIO::emitError("SQL Exception when inserting into table " . $multi_valued_tbl->getName(), $err[1], $err[2]);
                        }
                    }

                    if (isset($element['options']['many_to_many']) && $element['options']['many_to_many'] == 1) {
                        $element['join_table'] = Namer::getJoinTblName($params['form_name'], $element['name']);
                        $elements[$key]['join_table'] = $element['join_table']; //Foreach loop read-only work around
                        $schema = new Schema();
                        $join_tbl = $schema->createTable($element['join_table']);
                        $join_tbl->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
                        $join_tbl->setPrimaryKey(array("id"));
                        $join_tbl->addColumn("value_id", "integer", array("unsigned" => true));
                        $join_tbl->addColumn("form_instance_id", "integer", array("unsigned" => true));
                        $queries = $schema->toSql(new MySqlPlatform());
                        try {
                            $db->query($queries[0]);
                        } catch (\Exception $e) {
                            $db->rollback();
                            $err = array(1 => -1, 2 => "Unknown details");
                            foreach ($e->getTrace()[0]['args'] as $eElement) {
                                if ($eElement instanceof PDOException) {
                                    $err = $eElement->errorInfo;
                                    break;
                                }
                            }
                            return JsonIO::emitError("SQL Exception when creating table " . $join_tbl->getName(), $err[1], $err[2]);
                        }

                    }
                }
            }


            /* Creating the Form-Data-Instance table*/
            $schema = new Schema();
            $data_instance_tbl = $schema->createTable(Namer::getDataInstanceTblName($params['form_name']));
            $data_instance_tbl->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
            $data_instance_tbl->setPrimaryKey(array("id"));


            foreach ($elements as $element) {
                $options = isset($element['options']) ? $element['options'] : array();
                if ($element['schema']['type'] == 'text') {
                    if (!isset($element['options']['length'])) {
                        return JsonIO::emitError("Error! Text field doesn't contain length parameter");
                    }
                }
                $data_instance_tbl->addColumn($element['name'], $element['schema']['db_column_type'], $options);

                //TODO - For multi-valued fields add foreign key constraints
            }
            $queries = $schema->toSql(new MySqlPlatform());
            try {
                $db->query($queries[0]);
            } catch (\Exception $e) {
                $db->rollback();
                $err = array(1 => -1, 2 => "Unknown details");
                foreach ($e->getTrace()[0]['args'] as $eElement) {
                    if ($eElement instanceof PDOException) {
                        $err = $eElement->errorInfo;
                        break;
                    }
                }
                return JsonIO::emitError("SQL Exception when creating table " . $data_instance_tbl->getName(), $err[1], $err[2]);
            }


            /*Creating the Form-Schema-Instance function*/
            $schema = new Schema();
            $schema_instance_tbl = $schema->createTable(Namer::getSchemaInstanceTblName($params['form_name']));
            $schema_instance_tbl->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
            $schema_instance_tbl->setPrimaryKey(array("id"));
            $schema_instance_tbl->addColumn("elementId", "integer", array("unsigned" => true));
            $schema_instance_tbl->addColumn("col_name", "string", array("length" => 255));
            $schema_instance_tbl->addColumn("join_table", "string", array("length" => 255));
            $schema_instance_tbl->addColumn("foreign_table", "string", array("length" => 255));
            $schema_instance_tbl->addColumn("label", "string", array("length" => 255));
            $schema_instance_tbl->addColumn("min", "integer", array("notnull " => false));
            $schema_instance_tbl->addColumn("max", "integer", array("notnull" => false));
            $schema_instance_tbl->addColumn("options", "string", array("length" => 4096, "notnull" => false));
            $queries = $schema->toSql(new MySqlPlatform());

            try {
                $db->query($queries[0]);
            } catch (\Exception $e) {
                $db->rollback();
                $err = array(1 => -1, 2 => "Unknown details");
                foreach ($e->getTrace()[0]['args'] as $eElement) {
                    if ($eElement instanceof PDOException) {
                        $err = $eElement->errorInfo;
                        break;
                    }
                }
                return JsonIO::emitError("SQL Exception when creating table " . $schema_instance_tbl->getName(), $err[1], $err[2]);
            }

            /* Insert data into Form-Schema-Instance*/
            foreach ($elements as $element) {
                $values = array();
                $values['elementId'] = $element['schema']['id'];
                $values['col_name'] = preg_replace('/\s+/', '_', $element['name']);
                $values['join_table'] = (isset($element['join_table']) ? $element['join_table'] : "");
                $values['foreign_table'] = (isset($element['foreign_table']) ? $element['foreign_table'] : "");
                $values['label'] = $element['label'];
                $values['min'] = (isset($element['min']) ? $element['min'] : -1);
                $values['max'] = (isset($element['max']) ? $element['max'] : isset($element['options']['length']) ? $element['options']['length'] : -1);
                $values['options'] = json_encode($element['options']);
                try {
                    $db->insert(Namer::getSchemaInstanceTblName($params['form_name']), $values);
                } catch (\Exception $e) {
                    $db->rollback();
                    $err = array(1 => -1, 2 => "Unknown details");
                    foreach ($e->getTrace()[0]['args'] as $eElement) {
                        if ($eElement instanceof PDOException) {
                            $err = $eElement->errorInfo;
                            break;
                        }
                    }
                    return JsonIO::emitError("SQL Exception when inserting into table " . (Namer::getSchemaInstanceTblName($params['form_name'])));
                }

            }

            /* Create Form-Views Table */
            $schema = new Schema();
            $view_tbl = $schema->createTable(Namer::getViewTblName($params['form_name']));
            $view_tbl->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
            $view_tbl->setPrimaryKey(array("id"));
            $view_tbl->addColumn("name", "string", array("length" => 255));
            $view_tbl->addColumn("view_table", "string", array("length" => 255));
            $queries = $schema->toSql(new MySqlPlatform());
            try {
                $db->query($queries[0]);
            } catch (\Exception $e) {
                $db->rollback();
                $err = array(1 => -1, 2 => "Unknown details");
                foreach ($e->getTrace()[0]['args'] as $eElement) {
                    if ($eElement instanceof PDOException) {
                        $err = $eElement->errorInfo;
                        break;
                    }
                }
                return JsonIO::emitError("SQL Exception when creating table " . $view_tbl->getName(), $err[1], $err[2]);
            }


            $db->commit();
            return JsonIO::emit("Completed!");

        } catch (Exception $e) {
            $db->rollback();
            return JsonIO::emitError("Error Creating Form: " . $params['form_name'], JsonIO::BAD_REQUEST, $e->getMessage());
        }


    }

    public static function update($params)
    {
        Form_CRUD::setTableParams();
        if (!isset($params['form_id'])) {
            return JsonIO::emitError("Error! Parameters don't contain form id");
        }
        if (!isset($params['form_elements'])) {
            return JsonIO::emitError("Error! Parameters don't contain form elements");
        }

        if (!isset($params['form_name'])) {
            return JsonIO::emitError("Error! Parameters don't contain form name");
        }

        $db = DB_Conn::getDbConn();
        $db->beginTransaction();

        try {
            $existingForm = JsonIO::receive(Form_CRUD::read(array("id" => $params['form_id'])));
            if ($existingForm === array()) {
                return JsonIO::emitError("Invalid Form Id. Id " . $params['form_id']);
            }
            //TODO Allow to change form name - currently disallowed as this would mean changing other table names.
            $params['form_name'] = $existingForm['name'];
            $elements = $params['form_elements'];
            foreach ($elements as $key => $element) {

                if (isset($element['instance_id'])) {

                    $elements[$key]['old'] = JsonIO::receive(Schema_CRUD::read(array("table_name" => Namer::getSchemaInstanceTblName($existingForm['name']), "id" => $element['instance_id'])));
                    if ($elements[$key]['old'] === array()) {
                        unset($elements[$key]['old']);
                    } else {
                        $elements[$key]['old']['options'] = json_decode($elements[$key]['old']['options'], true); //Work Around since json-decode didn't do it recursively
                    }


                }
                $elements[$key]['schema'] = JsonIO::receive(Form_ELEMENTS_CRUD::read(array("id" => $element['id'])));
                if ($elements[$key]['schema'] === array()) {
                    return JsonIO::emitError("Invalid Form Element. Id " . $element['id']);
                }
            }

            /*Update all multivalued tables */
            foreach ($elements as $key => $element) {
                if ($element['schema']['multi_valued'] == 1) {
                    if (!isset($element['multi_values'])) {
                        return JsonIO::emitError("Error! MultiValued field doesn't contain values");
                    }
                    $element['foreign_table'] = Namer::getMultiValuedTblName($params['form_name'], $element['name']);
                    $elements[$key]['foreign_table'] = $element['foreign_table']; //Foreach loop read-only work around


                    foreach ($element['multi_values'] as $value) {
                        try {
                            if (isset($element['delete']) && $element['delete'] == 1) {
                                $db->delete($element['foreign_table'], array("id" => $element['instance_id']));
                            } else {
                                $db->insert($element['foreign_table'], array("value" => $value));
                            }

                        } catch (\Exception $e) {
                            $err = array(1 => -1, 2 => "Unknown details");
                            foreach ($e->getTrace()[0]['args'] as $eElement) {
                                if ($eElement instanceof PDOException) {
                                    $err = $eElement->errorInfo;
                                    break;
                                }
                            }
                            if ($err[1] == 1062) {
                                continue; //If there are repeated values, just ignore the errors
                            }
                            $db->rollback();
                            return JsonIO::emitError("SQL Exception when inserting into table " . $element['foreign_table'], $err[1], $err[2]);
                        }
                    }

                    /*Not Sure what kind of changes might be made to a many-to-many relationship*/
                    /*
                    if (isset($element['options']['many_to_many']) && $element['options']['many_to_many'] == 1) {
                        $element['join_table'] = Namer::getJoinTblName($params['form_name'], $element['name']);
                        $elements[$key]['join_table'] = $element['join_table']; //Foreach loop read-only work around
                        $schema = new Schema();
                        $join_tbl = $schema->createTable($element['join_table']);
                        $join_tbl->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
                        $join_tbl->setPrimaryKey(array("id"));
                        $join_tbl->addColumn("value_id", "integer", array("unsigned" => true));
                        $join_tbl->addColumn("form_instance_id", "integer", array("unsigned" => true));
                        $queries = $schema->toSql(new MySqlPlatform());
                        try {
                            $db->query($queries[0]);
                        } catch (\Exception $e) {
                            $db->rollback();
                            $err = $e->getTrace()[0]['args'][0]->errorInfo;
                            return JsonIO::emitError("SQL Exception when creating table " . $join_tbl->getName(), $err[1], $err[2]);
                        }

                    }*/
                }
            }


            /*Handling changes to Form-Data-Instance */
            $ex_schema = new Schema();
            $exs_data_instance_tbl = $ex_schema->createTable(Namer::getDataInstanceTblName($existingForm['name']));
            $exs_data_instance_tbl->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
            $exs_data_instance_tbl->setPrimaryKey(array("id"));


            foreach ($elements as $element) {

                if (!isset($element['old'])) { //If element is a new element
                    continue;
                }

                $options = isset($element['old']['options']) ? $element['old']['options'] : array();
                if ($element['schema']['type'] == 'text') {
                    if (!isset($element['old']['options']['length'])) {
                        print_r($element['old']);
                        return JsonIO::emitError("Error! Text  field doesn't contain length parameter");
                    }
                }
                $exs_data_instance_tbl->addColumn($element['old']['col_name'], $element['schema']['db_column_type'], $options);

                //TODO - For multi-valued fields add foreign key constraints
            }

            $schema = new Schema();
            $data_instance_tbl = $schema->createTable(Namer::getDataInstanceTblName($existingForm['name']));
            $data_instance_tbl->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
            $data_instance_tbl->setPrimaryKey(array("id"));


            foreach ($elements as $element) {
                if (isset($element['delete']) && $element['delete'] == 1) { //If an element is being deleted
                    continue;
                }
                $options = isset($element['options']) ? $element['options'] : array();
                if ($element['schema']['type'] == 'text') {
                    if (!isset($element['options']['length'])) {
                        return JsonIO::emitError("Error! Text field doesn't contain length parameter");
                    }

                }
                $data_instance_tbl->addColumn($element['name'], $element['schema']['db_column_type'], $options);

                //TODO - For multi-valued fields add foreign key constraints
            }
            $comparator = new Comparator();
            $schema_diff = $comparator->compare($ex_schema, $schema);
            $queries = $schema_diff->toSaveSql(new MySqlPlatform());
            $out = array();
            foreach ($queries as $query) {
                try {
                    $out[] = $db->query($query);
                } catch (\Exception $e) {
                    $db->rollback();
                    $err = array(1 => -1, 2 => "Unknown details");
                    foreach ($e->getTrace()[0]['args'] as $eElement) {
                        if ($eElement instanceof PDOException) {
                            $err = $eElement->errorInfo;
                            break;
                        }
                    }
                    return JsonIO::emitError("SQL Exception when updating table " . $data_instance_tbl->getName(), $err[1], $err[2]);
                }
            }

            /* Update data into Form-Schema-Instance*/
            foreach ($elements as $element) {
                $values = array();
                $values['elementId'] = $element['schema']['id'];
                $values['col_name'] = preg_replace('/\s+/', '_', $element['name']);
                $values['join_table'] = (isset($element['join_table']) ? $element['join_table'] : "");
                $values['foreign_table'] = (isset($element['foreign_table']) ? $element['foreign_table'] : "");
                $values['label'] = $element['label'];
                $values['min'] = (isset($element['min']) ? $element['min'] : -1);
                $values['max'] = (isset($element['max']) ? $element['max'] : isset($element['options']['length']) ? $element['options']['length'] : -1);
                $values['options'] = json_encode($element['options']);
                try {

                    if (isset($element['instance_id'])) {
                        if (isset($element['delete']) && $element['delete'] == 1) {
                            $out[] = $db->delete(Namer::getSchemaInstanceTblName($params['form_name']), array("id" => $element['instance_id']));
                        } else {
                            $out[] = $db->update(Namer::getSchemaInstanceTblName($params['form_name']), $values, array("id" => $element['instance_id']));
                        }
                    } else {
                        $out[] = $db->insert(Namer::getSchemaInstanceTblName($params['form_name']), $values);
                    }
                } catch (\Exception $e) {
                    $db->rollback();
                    $err = array(1 => -1, 2 => "Unknown details");
                    foreach ($e->getTrace()[0]['args'] as $eElement) {
                        if ($eElement instanceof PDOException) {
                            $err = $eElement->errorInfo;
                            break;
                        }
                    }
                    return JsonIO::emitError("SQL Exception when inserting into table " . (Namer::getSchemaInstanceTblName($params['form_name'])));
                }

            }

            return JsonIO::emit($out);


        } catch (\Exception $e) {
            $db->rollback();
            return JsonIO::emitError("Error Updating Form: " . $params['form_name'], JsonIO::BAD_REQUEST, $e->getMessage());
        }

    }

    public static function delete($params)
    {
        Form_CRUD::setTableParams();
        if (!isset($params['form_id'])) {
            return JsonIO::emitError("Error! Parameters don't contain form id");
        }

        $db = DB_Conn::getDbConn();
        $db->beginTransaction();
        try {
            $existingForm = JsonIO::receive(Form_CRUD::read(array("id" => $params['form_id'])));
            if ($existingForm === array()) {
                return JsonIO::emitError("Invalid Form Id. Id " . $params['form_id']);
            }
            $sm = $db->getSchemaManager();
            $fromSchema = $sm->createSchema();
            $toSchema = clone $fromSchema;

            $toSchema->dropTable(Namer::getViewTblName($existingForm['name']));
            $view_tbl = JsonIO::receive(View_CRUD::read(array("table_name" => Namer::getViewTblName($existingForm['name']))));
            foreach ($view_tbl as $view) {
                $toSchema->dropTable($view['view_table']);
            }

            $toSchema->dropTable(Namer::getSchemaInstanceTblName($existingForm['name']));
            $schema_tbl = JsonIO::receive(Schema_CRUD::read(array("table_name" => Namer::getSchemaInstanceTblName($existingForm['name']))));
            foreach ($schema_tbl as $col) {
                if ($col['join_table'] != "") {
                    $toSchema->dropTable($col['join_table']);
                }
                if ($col['foreign_table'] != "") {
                    $toSchema->dropTable($col['foreign_table']);
                }
            }


            //Check if data should be preserved
            if (!isset($params['save_data']) || (isset($params['save_data']) && $params['save_data'] == 0)) {
                $toSchema->dropTable(Namer::getDataInstanceTblName($existingForm['name']));
            }

            $sql = $fromSchema->getMigrateToSql($toSchema, $db->getDatabasePlatform());
            foreach ($sql as $s) {
                try {
                    $db->query($s);
                } catch (\Exception $e) {
                    $db->rollback();
                    $err = array(1 => -1, 2 => "Unknown details");
                    foreach ($e->getTrace()[0]['args'] as $eElement) {
                        if ($eElement instanceof PDOException) {
                            $err = $eElement->errorInfo;
                            break;
                        }
                    }
                    return JsonIO::emitError("SQL Exception when deleting tables ", $err[1], $err[2]);
                }
            }

            //Remove entry from form-list
            try {
                $db->delete(Config::$tables['FORM_LIST'], array("id" => $params['form_id']));
            } catch (\Exception $e) {
                $db->rollback();
                $err = array(1 => -1, 2 => "Unknown details");
                foreach ($e->getTrace()[0]['args'] as $eElement) {
                    if ($eElement instanceof PDOException) {
                        $err = $eElement->errorInfo;
                        break;
                    }
                }
                return JsonIO::emitError("SQL Exception when deleting entry in  " . Config::$tables['FORM_LIST'], $err[1], $err[2]);
            }

            return JsonIO::emit("Completed!");


        } catch (\Exception $e) {
            $db->rollback();
            return JsonIO::emitError("Error Deleting  Form: " . $params['form_id'], JsonIO::BAD_REQUEST, $e->getMessage());
        }

    }


}