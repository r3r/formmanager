<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 1/5/15
 * Time: 7:57 PM
 */

namespace src\components\meta\tests;


use app\includes\Test;
use src\components\meta\Form_CRUD;

class Form_CRUDTest extends Test
{

    public function testReadAll()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));
        $result = Form_CRUD::read(array());
        $this->validate($result, "read1Schema.json", __DIR__);

    }

    public function testReadSpecific()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));
        $result = Form_CRUD::read(array("id" => 1));
        $this->validate($result, "read1Schema.json", __DIR__);
    }


    public function testReadMultiplePresent()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));
        $result = Form_CRUD::read(array("id" => [1, 2, 3, 4]));
        $this->validate($result, "read2Schema.json", __DIR__);

    }

    public function testReadSingleNotPresent()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));
        $result = Form_CRUD::read(array("id" => 2));
        $this->validate($result, "read3Schema.json", __DIR__);

    }

    public function testReadMultipleNotPresent()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('form_list'));
        $result = Form_CRUD::read(array("id" => [0, 2, 4, 3]));
        $this->validate($result, "read4Schema.json", __DIR__);

    }


    public function astestCreate()
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
        $this->validate($result, "create1Schema.json", __DIR__);
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
 