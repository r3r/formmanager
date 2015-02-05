<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 2/4/15
 * Time: 11:18 PM
 */

namespace src\components\data;


use app\includes\JsonIO;
use src\components\db\DB_Conn;
use src\components\utils\Namer;

class CRUDS
{
    protected static $_data_table = NULL;
    protected static $_schema_table = NULL;
    protected static $_data_alias = NULL;
    protected static $_schema_alias = NULL;

    public static function setTableParams($form_name, $data_alias = 'dt', $schema_alias = 'sc')
    {
        self::$_data_table = Namer::getDataInstanceTblName($form_name);
        self::$_schema_table = Namer::getSchemaInstanceTblName($form_name);
        self::$_data_alias = $data_alias;
        self::$_schema_alias = $schema_alias;
    }

    public static function read($params)
    {
        if (isset($params['form_name'])) {
            self::setTableParams($params['form_name']);
        } else {
            return JsonIO::emitError("Parameters missing form name");
        }

        //Get schema of table
        $db = DB_Conn::getDbConn();
        $qBuilder = $db->createQueryBuilder();
        $qBuilder
            ->select('*')
            ->from(self::$_schema_table, self::$_schema_alias);

        $columns = $qBuilder->execute()->fetchAll();
        $simpleFields = array('id');
        $singleChoiceFields = array();
        $multiChoicedFields = array();
        foreach ($columns as $key => $column) {
            $columns[$key]['options'] = json_decode($column['options']); //to decode json_encoded options
            if ($column['join_table'] == '' && $column['foreign_table'] == '') {
                $simpleFields[] = $column['col_name'];
            }
            if ($column['join_table'] == '' && $column['foreign_table'] != '') {
                $singleChoiceFields[] = $column;
            }
            if ($column['join_table'] != '' && $column['foreign_table'] != '') {
                $multiChoicedFields[] = $column;
            }
        }


        //Get data

        $qBuilder = $db->createQueryBuilder();
        $qBuilder
            ->select($simpleFields)
            ->from(self::$_data_table, self::$_data_alias);
        $rows = $qBuilder->execute()->fetchAll();

        $choicedRows = array();
        foreach ($singleChoiceFields as $singleChoiceField) {
            $qBuilder = $db->createQueryBuilder();
            $qBuilder
                ->select('r.id', 'l.value')
                ->from($singleChoiceField['foreign_table'], 'l')
                ->innerJoin('l', self::$_data_table, 'r', 'r.' . $singleChoiceField['col_name'] . '=l.id')
                ->setMaxResults(1);
            $choicedRow = $qBuilder->execute()->fetchAll();
            if (is_array($choicedRow) && count($choicedRow) == 1) {
                $choicedRow = $choicedRow[0];
                $choicedRow['col_name'] = $singleChoiceField['col_name'];
                $choicedRows[] = $choicedRow;
            }
        }

        $multiChoicedRows = array();
        foreach ($multiChoicedFields as $multiChoicedField) {
            $qBuilder = $db->createQueryBuilder();
            $qBuilder
                ->select('main.id', 'label.value')
                ->from($multiChoicedField['foreign_table'], 'label')
                ->innerJoin('label', $multiChoicedField['join_table'], 'joiner', 'joiner.value_id=label.id')
                ->innerJoin('joiner', self::$_data_table, 'main', 'main.' . $multiChoicedField['col_name'] . '=joiner.form_instance_id');

            $multiChoicedRow = $qBuilder->execute()->fetchAll();
            if (is_array($multiChoicedRow)) {
                $multiChoicedRow['col_name'] = $multiChoicedField['col_name'];
                $multiChoicedRows[] = $multiChoicedRow;
            }
        }

        foreach ($rows as $key => $row) {
            foreach ($choicedRows as $choicedRow) {
                if ($row['id'] == $choicedRow['id']) {
                    $rows[$key][$choicedRow['col_name']] = $choicedRow['value'];
                    break;
                }
            }
            foreach ($multiChoicedRows as $multiChoicedRow) {
                $vals = array();
                $col_name = $multiChoicedRow['col_name'];
                unset($multiChoicedRow['col_name']);
                foreach ($multiChoicedRow as $choice) {
                    if ($row['id'] == $choice['id']) {
                        $vals[] = $choice['value'];
                    }
                }
                $rows[$key][$col_name] = $vals;
            }
        }


        //package data + schema and send it out

        $result = array("columns" => $columns, "rows" => $rows);
        return JsonIO::emitData($result);

    }
} 