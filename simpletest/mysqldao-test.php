<?php
require_once("/usr/lib/php5/simpletest/autorun.php");
require_once("../mysql_dao/mysqldao.php");
require_once("../mysql_dao/address.php");

class MySQLDAOTest extends UnitTestCase
{
    protected $address;
    protected $mysqli;

    public function setUp()
    {
        $this->address = new Address(null, "Foo Industries", "1313 Mockingbird Lane", "Suite 404", "Los Angeles", "CA", "90005-1313");
        $this->mysqli  = new mysqli("--HOSTNAME--", "--USERNAME--", "--PASSWORD--", "--DATABASE--");
    }

    /* these unit tests are disabled because the methods tested are protected
     * in order for them to pass, the dao_* methods in MySQLDAO must be changed
     * from protected to public temporarily */
/*    public function testGenerateBindParametersWithPrimaryKey()
    {
        $expectedFields = array("issssss", $this->address->getId(), $this->address->getName(), $this->address->getStreet1(),
                                $this->address->getStreet2(), $this->address->getCity(), $this->address->getState(), $this->address->getZip());
        $fieldList      = $this->address->dao_generateFieldList(false);
        $fieldArray     = $this->address->dao_generateBindParameters($fieldList);

        $this->assertEqual($fieldArray, $expectedFields);
    }

    public function testGenerateBindParametersWithoutPrimaryKey()
    {
        $expectedFields = array("ssssss", $this->address->getName(), $this->address->getStreet1(),
                                $this->address->getStreet2(), $this->address->getCity(), $this->address->getState(), $this->address->getZip());
        $fieldList      = $this->address->dao_generateFieldList(true);
        $fieldArray     = $this->address->dao_generateBindParameters($fieldList);

        $this->assertEqual($fieldArray, $expectedFields);
    }

    public function testGenerateParameters()
    {
        $expectedParameters = "?, ?, ?, ?, ?, ?, ?";
        $actualParameters   = $this->address->dao_generateParameters(7);

        $this->assertEqual($actualParameters, $expectedParameters);
    } */

    public function testDelete()
    {
        $this->address->insert($this->mysqli);
        echo "<p>Deleted " . $this->address->getId() . "</p>";
        $this->address->delete($this->mysqli);
    }

    public function testInsert()
    {
        $this->address->insert($this->mysqli);
        $this->assertNotNull($this->address->getId());
        $this->assertTrue($this->address->getId() > 0);
    }

    public function testUpdate()
    {
        $this->address->insert($this->mysqli);
        $this->address->setStreet2("Building 404");
        $this->address->update($this->mysqli);
    }

    public function tearDown()
    {
        $this->mysqli->close();
    }
}
?>
