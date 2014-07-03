php5-mysqldao
=============

mySQL DAO Design Pattern for PHP.

This class is the abstract class for the mySQL Data Access Object (DAO) pattern. Each subclass of this class will receive the following public methods:

- delete(&$mysqli);
- insert(&$mysqli);
- update(&$mysqli);

In order to use the DAO pattern, subclasses must have the following protected static variables:

- dao_primaryKey: string containing the primary key's field name
- dao_tableName: string containing the mySQL table name
- dao_typeMap: array containing "field" => "type" pairs, where field is the field name and type
is the data type according to mysqli_stmt::bind_param().

An example PHP class using the DAO pattern is included.
