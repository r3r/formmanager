<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 1/10/15
 * Time: 1:01 PM
 */

namespace app\includes;

require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . "vendor"
    . DIRECTORY_SEPARATOR
    . "geraintluff" . DIRECTORY_SEPARATOR . "jsv4" . DIRECTORY_SEPARATOR . "jsv4.php");
use Jsv4;
use PDO;

class Test extends \PHPUnit_Extensions_Database_TestCase
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

    public function validate($json_obj, $schema_name,
                             $fixture_base, $dir = "/fixtures/")
    {
        $schema = json_decode(file_get_contents(realpath($fixture_base .
            $dir . $schema_name)));
        if (is_string($json_obj)) {
            $json_obj = json_decode($json_obj);
        }
        $res = Jsv4::validate($json_obj, $schema);

        $this->assertTrue($res->valid, json_encode(array("errors" =>
            $res->errors, "object" => $json_obj)));
    }

    public function getDataSet()
    {

    }
}