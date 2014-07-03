<?php
require_once("mysqldao.php");

/**
 * example class demonstrating the use of MySQLDAO
 *
 * This is a typical example of a class that represents a row in a mySQL table. Notice the class
 * is simple and clean and consists of only typical accessor and mutator methods. All one needs to
 * do is extend the MySQLDAO class and include the static DAO related variables to deploy the DAO pattern.
 *
 * @author Dylan McDonald <dylanm@deepdivecoders.com>
 **/
class Address extends MySQLDAO
{
    /**
     * id of the address; this is the primary key
     **/
    protected $id;
    /**
     * name of the person or company
     **/
    protected $name;
    /**
     * first line of the street address
     **/
    protected $street1;
    /**
     * second line of the street address
     **/
    protected $street2;
    /**
     * city of the street address
     **/
    protected $city;
    /**
     * state of the street address
     **/
    protected $state;
    /**
     * ZIP code of the street address
     **/
    protected $zip;

    /**
     * MySQLDAO's name of the primary key
     **/
    protected static $dao_primaryKey = "id";
    /**
     * MySQLDAO's name of the table
     **/
    protected static $dao_tableName  = "address";
    /**
     * MySQLDAO's map of the data types
     **/
    protected static $dao_typeMap    = array("id"  => "i", "name"  => "s", "street1" => "s", "street2" => "s",
                                            "city" => "s", "state" => "s", "zip"     => "s");

    /**
     * constructor for Address
     *
     * @param integer new value of id
     * @param string new value of name
     * @param string new value of first line of street address
     * @param string new value of second line of street address
     * @param string new value of city
     * @param string new value of state
     * @param string new value of zip
     * @throws RuntimeException if invalid inputs detected
     **/
    public function __construct($newId, $newName, $newStreet1, $newStreet2, $newCity, $newState, $newZip)
    {
        try
        {
            $this->setId($newId);
            $this->setName($newName);
            $this->setStreet1($newStreet1);
            $this->setStreet2($newStreet2);
            $this->setCity($newCity);
            $this->setState($newState);
            $this->setZip($newZip);
        }
        catch(RuntimeException $exception)
        {
            throw(new RuntimeException("Unable to create address"));
        }
    }

    /**
     * accessor method for id
     *
     * @return integer value of id
     **/
    public function getId()
    {
        return($this->id);
    }
    /**
     * mutator method for id
     *
     * @param integer new value of id
     * @throws RangeException if id is negative
     * @throws UnexpectedValueException if id is invalid
     **/
    public function setId($newId)
    {
        // allow for a null id if this is a new object
        if($newId === null)
        {
            $this->id = null;
            return;
        }

        // first, scrub out obvious trash
        $newId = htmlspecialchars($newId);
        $newId = trim($newId);

        // second, verify it's numeric
        if(is_numeric($newId) === false)
        {
            throw(new UnexpectedValueException("Invalid id: $newId"));
        }

        // third, convert it, and verify it's in range
        $newId = intval($newId);
        if($newId < 0)
        {
            throw(new RangeException("Invalid id: $newId"));
        }

        // finally, it's cleansed - assign it to the object
        $this->id = $newId;
    }

    /**
     * accessor method for name
     *
     * @return string value of name
     **/
    public function getName()
    {
        return($this->name);
    }

    /**
     * mutator method for name
     *
     * @param string new value of name
     * @throws UnexpectedValueException if name is empty
     **/
    public function setName($newName)
    {
        // first, scrub out obvious trash
        $newName = htmlspecialchars($newName);
        $newName = trim($newName);

        // second, verify the variable still has something left
        if(empty($newName) === true)
        {
            throw(new UnexpectedValueException("Invalid name: $newName"));
        }

        // finally, it's cleansed - assign it to the object
        $this->name = $newName;
    }

    /**
     * accessor method for first line of street address
     *
     * @return string value of first line of street address
     **/
    public function getStreet1()
    {
        return($this->street1);
    }

    /**
     * mutator method for first line of street address
     *
     * @param string new value of first line of street address
     * @throws UnexpectedValueException if location is empty
     **/
    public function setStreet1($newStreet1)
    {
        // first, scrub out obvious trash
        $newStreet1 = htmlspecialchars($newStreet1);
        $newStreet1 = trim($newStreet1);

        // second, verify the variable still has something left
        if(empty($newStreet1) === true)
        {
            throw(new UnexpectedValueException("Invalid street1: $newStreet1"));
        }

        // finally, it's cleansed - assign it to the object
        $this->street1 = $newStreet1;
    }

    /**
     * accessor method for second line of street address
     *
     * @return string value of second line of street address
     **/
    public function getStreet2()
    {
        return($this->street2);
    }

    /**
     * mutator method for second line of street address
     *
     * @param string new value of second line of street address
     **/
    public function setStreet2($newStreet2)
    {
        // first, scrub out obvious trash
        $newStreet2 = htmlspecialchars($newStreet2);
        $newStreet2 = trim($newStreet2);

        // second, if nothing's left, this field can be null
        if(empty($newStreet2) === true)
        {
            $this->street2 = null;
        }

        // finally, it's cleansed - assign it to the object
        $this->street2 = $newStreet2;
    }

    /**
     * accessor method for city
     *
     * @return string value of city
     **/
    public function getCity()
    {
        return($this->city);
    }

    /**
     * mutator method for city
     *
     * @param string new value of city
     * @throws UnexpectedValueException if location is empty
     **/
    public function setCity($newCity)
    {
        // first, scrub out obvious trash
        $newCity = htmlspecialchars($newCity);
        $newCity = trim($newCity);

        // second, verify the variable still has something left
        if(empty($newCity) === true)
        {
            throw(new UnexpectedValueException("Invalid city: $newCity"));
        }

        // finally, it's cleansed - assign it to the object
        $this->city = $newCity;
    }

    /**
     * accessor method for state
     *
     * @return string value of state
     **/
    public function getState()
    {
        return($this->state);
    }

    /**
     * mutator method for state
     *
     * @param string new value of state
     * @throws UnexpectedValueException if state is invalid
     **/
    public function setState($newState)
    {
        // first, scrub out obvious trash
        $newState = htmlspecialchars($newState);
        $newState = trim($newState);

        // enforce the USPS abbreviation
        if(preg_match("/^[A-Z]{2}$/", $newState) !== 1)
        {
            throw(new UnexpectedValueException("Invalid state: $newState"));
        }

        // finally, it's cleansed - assign it to the object
        $this->state = $newState;
    }

    /**
     * accessor method for zip
     *
     * @return string value of zip
     **/
    public function getZip()
    {
        return($this->zip);
    }

    /**
     * mutator method for zip
     *
     * @param string new value of zip
     * @throws UnexpectedValueException if zip is invalid
     **/
    public function setZip($newZip)
    {
        // first, scrub out obvious trash
        $newZip = htmlspecialchars($newZip);
        $newZip = trim($newZip);

        // enforce the USPS standard
        if(preg_match("/^\d{5}(-\d{4})?$/", $newZip) !== 1)
        {
            throw(new UnexpectedValueException("Invalid ZIP code: $newZip"));
        }

        // finally, it's cleansed - assign it to the object
        $this->zip = $newZip;
    }
}
?>
