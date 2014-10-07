<?php
/**
 * abstract class for the mySQL DAO design pattern
 *
 * This class is the abstract class for the mySQL Data Access Object (DAO) pattern.
 * Each subclass of this class will receive the following public methods:
 *
 * - delete(&$mysqli);
 * - insert(&$mysqli);
 * - update(&$mysqli);
 * - Table::getTableByField(&$mysqli, $fieldValue)
 *
 * Note that the static method is dynamically generated based on the field that is queried.
 * In order to use the DAO pattern, subclasses must have the following protected static variables:
 *
 * - dao_primaryKey: string containing the primary key's field name
 * - dao_tableName: string containing the mySQL table name
 * - dao_typeMap: array containing "field" => "type" pairs, where field is the field name and type
 * is the data type according to mysqli_stmt::bind_param().
 *
 * @author Dylan McDonald <dmcdonald21@cnm.edu>
 * @see <http://www.php.net/manual/en/mysqli-stmt.bind-param.php>
 **/
abstract class MySQLDAO
{
    /**
     *
     * dynamically provides getTableByField() static methods
     *
     * The method name must conform to getTableByField() and take exactly two parameters:
     *
     * - $mysqli: pointer to a mySQL connection
     * - $fieldValue: value to search for
     *
     * @param string $methodName method name being called
     * @param array $argv arguments to the array
     * @return mixed object or array objects if found, null if not
     * @throws BadMethodCallException if the method name is malformed
     * @throws mysqli_sql_exception if a mySQL error occurs
     * @throws ReflectionException if the class cannot be reflected
     * @throws DomainException if the SQL query returns empty set
     **/
    public static function __callStatic($methodName, $argv)
    {
        // verify the method actually should exist: getFooByBar()
        $className = get_called_class();
        $matches   = array();
        if(preg_match("/^get${className}By(\w+)$/", $methodName, $matches) !== 1)
        {
            throw(new BadMethodCallException("Unable to find method: $methodName"));
        }

        // verify the field exists    
        $field = lcfirst($matches[1]);
        if(isset(static::$dao_typeMap[$field]) === false)
        {
            throw(new BadMethodCallException("Unable to find field: $field"));
        }
        $fieldType = static::$dao_typeMap[$field];

        // extract arguments
        if(count($argv) !== 2)
        {
            throw(new BadMethodCallException("Incorrect number of arguments: " . count($argv) . ", expected 2"));
        }
        $mysqli = $argv[0];
        $search = $argv[1];

        // now starts the main mySQL part...
        // ...handle degenerate cases
        if(is_object($mysqli) === false || get_class($mysqli) !== "mysqli")
        {
            throw(new mysqli_sql_exception("Non mySQL pointer detected"));
        }

        // create query template
        $fieldList = static::dao_generateFieldList(false);
        $query     = "SELECT $fieldList FROM " . static::$dao_tableName . " WHERE $field = ?";

        // prepare the query statement
        $statement = $mysqli->prepare($query);
        if($statement === false)
        {
            throw(new mysqli_sql_exception("Unable to prepare statement: $query"));
        }

        // bind parameters to the query template
        $argv     = array();
        $argv[0]  = $fieldType;
        $argv[1]  = &$search;
        $wasClean = call_user_func_array(array($statement, "bind_param"), $argv);
        if($wasClean === false)
        {
            throw(new mysqli_sql_exception("Unable to bind parameters"));
        }

        // okay now do it
        if($statement->execute() === false)
        {
            throw(new mysqli_sql_exception("Unable to execute statement"));
        }

        $dataset = array();
        $result  = $statement->get_result();
        while(($row = $result->fetch_assoc()) !== null)
        {
            // reflect the class
            try
            {
                $reflector  = new ReflectionClass(get_called_class());
                $item       = $reflector->newInstanceWithoutConstructor();
                $properties = $reflector->getProperties();
                foreach($properties as $property)
                {
                    // add member variables from the mySQL row
                    $propertyName = $property->getName();
                    if(array_key_exists($property->getName(), $row) === true)
                    {
                        $property->setAccessible(true);
                        $property->setValue($item, $row[$propertyName]);
                    }
                }
            }
            catch(ReflectionException $exception)
            {
                throw(new ReflectionException("Unable to reflect " . get_called_class(), 0, $exception));
            }
            
            // re-reflect the class and add it to the result array
            $dataset[] = $item;
        }

        // clean up the statement
        $statement->close();
        
        // decide what to return...
        // ... empty set? throw an exception
        if(empty($dataset))
        {
            throw(new DomainException("Unable to get " . get_called_class() . " by $field: empty set"));
        }
        // ...one result? return just the item
        else if(count($dataset) === 1)
        {
            return($dataset[0]);
        }
        // many results? return the entire array
        else
        {
            return($dataset);
        }
    }

    /**
     * generates an array to pass to mysqli_stmt::bind_param()
     *
     * @param string $fieldList comma separated field list
     * @return array arguments to pass to mysqli_stmt::bind_param()
     * @see <http://www.php.net/manual/en/mysqli-stmt.bind-param.php>
     **/
    protected function dao_generateBindParameters($fieldList)
    {
        // extract the fields from the field list
        $types  = "";
        $fields = explode(", ", $fieldList);
        $argv   = array("types");

        // build up the fields and parameter types
        foreach($fields as $field)
        {
            $argv[] = &$this->$field;
            $types  = $types . static::$dao_typeMap[$field];
        }
        $argv[0] = $types;

        return($argv);
    }

    /**
     * generates a comma separated field list
     *
     * @param boolean $omitPrimaryKey whether to omit the primary key
     * @return string comma separated field list
     **/
    protected static function dao_generateFieldList($omitPrimaryKey)
    {
        // get the fields & filter the primary key
        $fields = get_class_vars(get_called_class());
        if($omitPrimaryKey === true)
        {
            unset($fields[static::$dao_primaryKey]);
        }

        // delete internal DAO related fields
        unset($fields["dao_primaryKey"]);
        unset($fields["dao_tableName"]);
        unset($fields["dao_typeMap"]);

        // build the field list
        $fieldList = "";
        foreach($fields as $field => $ignored)
        {
            $fieldList = "$fieldList$field, ";
        }
        $fieldList = substr($fieldList, 0, -2);

        return($fieldList);
    }

    /**
     * generates placeholders for an INSERT query template
     *
     * @param int $numFields number of fields to template
     * @return string templated placeholders
     **/
    protected function dao_generateParameters($numFields)
    {
        $parameters = str_repeat("?, ", $numFields);
        $parameters = substr($parameters, 0, -2);
        return($parameters);
    }

    /**
     * generates placeholders for an UPDATE query template
     *
     * @param string $fieldList comma separated field list
     * @return string templated placeholders
     **/
    protected function dao_generateUpdateParameters($fieldList)
    {
        $fieldList = str_replace(",", " = ?,", $fieldList) . " = ?";
        return($fieldList);
    }

    /**
     * deletes this object from mySQL
     *
     * @param resource &$mysqli pointer to valid mysqli connection
     * @throws mysqli_sql_exception if a mySQL error occurs
     **/
    public function delete(&$mysqli)
    {
        // handle degenerate cases
        if(is_object($mysqli) === false || get_class($mysqli) !== "mysqli")
        {
            throw(new mysqli_sql_exception("Non mySQL pointer detected"));
        }

        // ensure the primary key is null
        if(empty(static::$dao_primaryKey) === false && $this->{static::$dao_primaryKey} === null)
        {
            throw(new mysqli_sql_exception("Unable to delete record: primary key is null"));
        }

        // create query template
        $query = "DELETE FROM " . static::$dao_tableName . " WHERE " . static::$dao_primaryKey . " = ?";

        // prepare the query statement
        $statement = $mysqli->prepare($query);
        if($statement === false)
        {
            throw(new mysqli_sql_exception("Unable to prepare statement: $query"));
        }

        // bind parameters to the query template
        $argv[0]  = static::$dao_typeMap[static::$dao_primaryKey];
        $argv[1]  = &$this->{static::$dao_primaryKey};
        $wasClean = call_user_func_array(array($statement, "bind_param"), $argv);
        if($wasClean === false)
        {
            throw(new mysqli_sql_exception("Unable to bind parameters"));
        }

        // okay now do it
        if($statement->execute() === false)
        {
            throw(new mysqli_sql_exception("Unable to execute statement"));
        }

        // clean up the statement
        $statement->close();
    }

    /**
     * inserts this object to mySQL
     *
     * @param resource &$mysqli pointer to valid mysqli connection
     * @throws mysqli_sql_exception if a mySQL error occurs
     **/
    public function insert(&$mysqli)
    {
        // handle degenerate cases
        if(is_object($mysqli) === false || get_class($mysqli) !== "mysqli")
        {
            throw(new mysqli_sql_exception("Non mySQL pointer detected"));
        }

        // ensure the primary key is null
        if(empty(static::$dao_primaryKey) === false && $this->{static::$dao_primaryKey} !== null)
        {
            throw(new mysqli_sql_exception("Unable to insert record: primary key is not null"));
        }

        // create query template
        $fieldList  = static::dao_generateFieldList(true);
        $parameters = $this->dao_generateParameters(substr_count($fieldList, ",") + 1);
        $query      = "INSERT INTO " . static::$dao_tableName . "($fieldList) VALUES($parameters)";

        // prepare the query statement
        $statement = $mysqli->prepare($query);
        if($statement === false)
        {
            throw(new mysqli_sql_exception("Unable to prepare statement: $query"));
        }

        // bind parameters to the query template
        $argv     = $this->dao_generateBindParameters($fieldList);
        $wasClean = call_user_func_array(array($statement, "bind_param"), $argv);
        if($wasClean === false)
        {
            throw(new mysqli_sql_exception("Unable to bind parameters"));
        }

        // okay now do it
        if($statement->execute() === false)
        {
            throw(new mysqli_sql_exception("Unable to execute statement"));
        }

        // clean up the statement
        $statement->close();

        // update primary key if it's an integer
        if(empty(static::$dao_primaryKey) === false && static::$dao_typeMap[static::$dao_primaryKey] === "i")
        {
            $this->{static::$dao_primaryKey} = $mysqli->insert_id;
        }
    }

    /**
     * updates this object in mySQL
     *
     * @param resource &$mysqli pointer to valid mysqli connection
     * @throws mysqli_sql_exception if a mySQL error occurs
     **/
    public function update(&$mysqli)
    {
        // handle degenerate cases
        if(is_object($mysqli) === false || get_class($mysqli) !== "mysqli")
        {
            throw(new mysqli_sql_exception("Non mySQL pointer detected"));
        }

        // ensure the primary key is not null
        if(empty(static::$dao_primaryKey) === false && $this->{static::$dao_primaryKey} === null)
        {
            throw(new mysqli_sql_exception("Unable to update record: primary key is not null"));
        }

        // create query template
        $fieldList  = static::dao_generateFieldList(true);
        $parameters = $this->dao_generateUpdateParameters($fieldList);
        $query      = "UPDATE " . static::$dao_tableName . " SET $parameters WHERE " . static::$dao_primaryKey . " = ?";

        // prepare the query statement
        $statement = $mysqli->prepare($query);
        if($statement === false)
        {
            throw(new mysqli_sql_exception("Unable to prepare statement: $query"));
        }

        // bind parameters to the query template, adding the primary key at the end
        $argv     = $this->dao_generateBindParameters($fieldList);
        $argv[]   = &$this->{static::$dao_primaryKey};
        $argv[0]  = $argv[0] . static::$dao_typeMap[static::$dao_primaryKey];
        $wasClean = call_user_func_array(array($statement, "bind_param"), $argv);
        if($wasClean === false)
        {
            throw(new mysqli_sql_exception("Unable to bind parameters"));
        }

        // okay now do it
        if($statement->execute() === false)
        {
            throw(new mysqli_sql_exception("Unable to execute statement"));
        }

        // clean up the statement
        $statement->close();
    }
}
?>
