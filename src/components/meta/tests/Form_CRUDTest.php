<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 1/5/15
 * Time: 7:57 PM
 */

namespace src\components\meta\tests;

use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;
use PDO;
use src\components\meta\Form_CRUD;

class Form_CRUDTest extends \PHPUnit_Extensions_Database_TestCase
{
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    //JSON Retriever
    static private $retriever;

    //JSON Validator
    static private $validator;

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

    /**
     * @override
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$retriever = new UriRetriever();
        self::$validator = new Validator();
    }

    public function validate($json_obj, $schema_name, $dir = "/fixtures/")
    {
        $schema = self::$retriever->retrieve('file:///' . realpath(__DIR__ . $dir . $schema_name));
        self::$validator->check($json_obj, $schema);
        $this->assertTrue(self::$validator->isValid(), json_encode(array("errors" => self::$validator->getErrors(), "object" => $json_obj)));
    }

    public function testReadAll()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));
        $result = json_decode(Form_CRUD::read(array()));
        $this->validate($result, "read1Schema.json");

    }

    public function testReadSpecific()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));

        $result = json_decode(Form_CRUD::read(array("id" => 1)));
        $this->validate($result, "read1Schema.json");

    }


    public function testReadMultiplePresent()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));

        $result = json_decode(Form_CRUD::read(array("id" => [1, 2, 3, 4])));
        $this->validate($result, "read2Schema.json");

    }

    public function testReadSingleNotPresent()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));

        $result = json_decode(Form_CRUD::read(array("id" => 2)));
        $this->validate($result, "read3Schema.json");

    }

    public function testReadMultipleNotPresent()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));

        $result = json_decode(Form_CRUD::read(array("id" => [0, 2, 4, 3])));
        $this->validate($result, "read4Schema.json");

    }


    public function testCreate()
    {
        //TODO - Set up database by dropping all tables
        //TODO - Validate the creation of the tables and their columns, etc
        $params = array(
            "form_name" => "Q",
            "form_elements" => array(
                array(
                    "id" => 1,
                    "label" => "Visible Label 1",
                    "name" => "First Field",
                    "options" => array(
                        "length" => 255
                    )
                ),
                array(
                    "id" => 2,
                    "label" => "Visible Label 2",
                    "name" => "Second Field",
                    "options" => array(
                        "length" => 255,
                        "many_to_many" => 1
                    ),
                    "multi_values" => array(
                        "value1",
                        "value2"
                    )

                )
            )
        );
        $result = json_decode(Form_CRUD::create($params));
        $this->validate($result, "create1Schema.json");

    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        $dataSet = new \PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataSet->addTable('form_list', dirname(__FILE__) . "/fixtures/form_list.csv");
        $dataSet->addTable('form_elements', dirname(__FILE__) . "/fixtures/form_elements.csv");
        return $dataSet;
    }
}
 