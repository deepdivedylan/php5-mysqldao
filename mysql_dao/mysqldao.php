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
 *
 * In order to use the DAO pattern, subclasses must have the following protected static variables:
 * 
 * - dao_primaryKey: string containing the primary key's field name
 * - dao_tableName: string containing the mySQL table name
 * - dao_typeMap: array containing "field" => "type" pairs, where field is the field name and type
 * is the data type according to mysqli_stmt::bind_param().
 *
 * @author Dylan McDonald <dylanm@deepdivecoders.com>
 * @see <http://www.php.net/manual/en/mysqli-stmt.bind-param.php>
 **/
abstract class MySQLDAO
{
    /**
     * generates an array to pass to mysqli_stmt::bind_param()
     *
     * @param string comma separated field list
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
     * @param boolean whether to omit the primary key
     * @return string comma separated field list
     **/
    protected function dao_generateFieldList($omitPrimaryKey)
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
     * @param integer number of fields to template
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
     * @param string comma separated field list
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
     * @param mysqli pointer to valid mysqli connection
     * @throws mysqli_sql_exception if a mySQL error occurs
     **/
    public function delete(&$mysqli)
    {
        // handle degenerate cases
        if(is_object($mysqli) === false || get_class($mysqli) !== "mysqli")
        {
            throw(new Exception("Non mySQL pointer detected"));
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
     * @param mysqli pointer to valid mysqli connection
     * @throws mysqli_sql_exception if a mySQL error occurs
     **/
    public function insert(&$mysqli)
    {
        // handle degenerate cases
        if(is_object($mysqli) === false || get_class($mysqli) !== "mysqli")
        {
            throw(new Exception("Non mySQL pointer detected"));
        }

        // ensure the primary key is null
        if(empty(static::$dao_primaryKey) === false && $this->{static::$dao_primaryKey} !== null)
        {
            throw(new mysqli_sql_exception("Unable to insert record: primary key is not null"));
        }

        // create query template
        $fieldList  = $this->dao_generateFieldList(true);
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
     * @param mysqli pointer to valid mysqli connection
     * @throws mysqli_sql_exception if a mySQL error occurs
     **/
    public function update(&$mysqli)
    {
        // handle degenerate cases
        if(is_object($mysqli) === false || get_class($mysqli) !== "mysqli")
        {
            throw(new Exception("Non mySQL pointer detected"));
        }

        // ensure the primary key is not null
        if(empty(static::$dao_primaryKey) === false && $this->{static::$dao_primaryKey} === null)
        {
            throw(new mysqli_sql_exception("Unable to update record: primary key is not null"));
        }

        // create query template
        $fieldList  = $this->dao_generateFieldList(true);
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
