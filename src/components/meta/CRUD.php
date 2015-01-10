<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 12/30/14
 * Time: 2:56 PM
 */

namespace src\components\meta;

use app\includes\JsonIO;
use src\components\db\DB_Conn;

abstract class CRUD
{

    protected static $_table = NULL;
    protected static $_alias = NULL;

    public static function read($params)
    {
        $id = NULL;
        if (isset($params['id'])) {
            $id = $params['id'];
        } else if (is_array($params)) {
            foreach ($params as $param) {
                if (is_numeric($param)) {
                    if ($id == NULL) {
                        $id = array();
                    }
                    $id[] = $param;
                }
            }
        }

        $db = DB_Conn::getDbConn();
        $qBuilder = $db->createQueryBuilder();
        $qBuilder
            ->select('*')
            ->from(CRUD::$_table, CRUD::$_alias);

        if (is_numeric($id)) {
            $qBuilder
                ->where(
                    $qBuilder->expr()->eq('id', ':id')
                )
                ->setParameter(':id', $id);

        } else if (is_array($id)) {
            $id = array_map("intval", $id);
            $qBuilder
                ->where('id IN (:id)')
                ->setParameter(':id', $id, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }
        $res = $qBuilder->execute()->fetchAll();
        if (!empty($res)) {
            return JsonIO::emitData($res);
        } else {
            if (!is_array($id)) {
                $id = array($id);
            }
            $ids = implode(", ", $id);
            return JsonIO::emitError("Record not found", "Id(s): " . $ids,
                JsonIO::NOT_FOUND);
        }

    }

    public static function create($params)
    {
        if (!isset($params['form_elements'])) {
            return JsonIO::emitError("Error! Parameters don't contain form elements");
        }

        $elementIds = $params['form_elements'];

        return JsonIO::emitSuccess(Form_ELEMENTS_CRUD::read(array("id" =>
            $elementIds)));
    }

    public static function update($params)
    {

    }

    public static function delete($params)
    {

    }
} 