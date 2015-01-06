<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 1/5/15
 * Time: 7:57 PM
 */

namespace src\components\meta\tests;

use PDO;
use src\components\meta\Form_CRUD;

class Form_CRUDTest extends \PHPUnit_Extensions_Database_TestCase
{
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {

                self::$pdo = new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
        }

        return $this->conn;

    }

    public function testRead()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));

        $result = json_decode(Form_CRUD::read(array("id" => 1)), true);
        if (!isset($result['data'])) {
            $this->assertTrue(FALSE, "Result doesn't contain element data.");
        } else {
            $data = $result['data'];
            $this->assertEquals(1, count($data), "data contains more than one element");
            $element = $data[0];
            $this->assertEquals(1, $element['id']);
            $this->assertEquals("Test Form", $element['name']);
        }

    }

    public function testRead2()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));

        $result = json_decode(Form_CRUD::read(array("id" => [1, 2, 3, 4])), true);
        if (!isset($result['data'])) {
            $this->assertTrue(FALSE, "Result doesn't contain element data.");
        } else {
            $data = $result['data'];
            $this->assertEquals(1, count($data), "data contains more than one element");
            $element = $data[0];
            $this->assertEquals(1, $element['id']);
            $this->assertEquals("Test Form", $element['name']);
        }

    }

    public function testRead3()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));

        $result = json_decode(Form_CRUD::read(array("id" => 2)), true);
        if (!isset($result['data'])) {
            $this->assertTrue(FALSE, "Result doesn't contain element data.");
        } else {
            $data = $result['data'];
            $this->assertEquals(0, count($data), "data contains more than one element");
        }

    }

    public function testRead4()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));

        $result = json_decode(Form_CRUD::read(array("id" => [0, 2, 4, 3])), true);
        if (!isset($result['data'])) {
            $this->assertTrue(FALSE, "Result doesn't contain element data.");
        } else {
            $data = $result['data'];
            $this->assertEquals(0, count($data), "data contains more than one element");
        }

    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        $dataSet = new \PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataSet->addTable('form_list', dirname(__FILE__) . "/fixtures/form_list.csv");
        return $dataSet;
    }
}
 