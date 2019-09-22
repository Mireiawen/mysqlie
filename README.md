# MySQLie

Extended MySQLi classes. All of the methods that can return error, should throw exceptions when and only error occurs.

There are also some custom methods added to make some tasks easier.

* Classes: `MySQLie`, `MySQLie_stmt`
* Namespace: `Mireiawen\MySQLie`

## Requirements
* MySQL Native Driver (mysqlnd)
* PHP 7

## Installation
You can clone or download the code from the [GitHub repository](https://github.com/Mireiawen/mysqlie) or you can use composer: `composer require mireiawen/mysqlie`

## Methods
Only the custom methods are listed here, the overridden methods should behave
like the original methods, except that the errors are thrown.

### Class MySQLie
The main MySQLie class

#### escape_identifier
    MySQLie::escape_identifier(string $name)

Escape an identifier name for the SQL query

##### Arguments
* **string** `$name` - The identifier name to escape

##### Return value
* **string** - The escaped identifier name

#### escape_query_identifiers
    MySQLie::escape_query_identifiers(string $sql, array $identifiers)

Escape the identifiers in the SQL query

##### Arguments
* **string** $sql - SQL query with placeholders (`%s`) for the identifiers
* **string[]** $identifiers - Array of the identifiers to be escaped and placed into the query

##### Return value
* **string** - The SQL query with identifiers escaped and placed into the query

#### foreign_key_checks
    MySQLie::foreign_key_checks(bool $state)

Set the foreign key checks on or off
	
##### Arguments
* **bool** $state - TRUE to enable foreign key checks, FALSE to disable them

##### Exceptions thrown
###### \Exception
* In case of database errors

#### get_autocommit
    MySQLie::get_autocommit()
    
##### Return value
* **bool** - The current `AUTOCOMMIT` status, TRUE if it is on, FALSE if it is off

##### Exceptions thrown
###### \Exception
* In case of database errors
	
#### get_autoincrement
    MySQLie::get_autoincrement(string $table)

Get the table auto increment value

##### Arguments
* **string** `$table` - The name of the table to get the `AUTO_INCREMENT` value from

##### Return value
* **int** - The value of the table `AUTO_INCREMENT`

##### Exceptions thrown
###### \Exception
* In case of database errors

#### truncate
    MySQLie::truncate(string $table)
	
Truncates the given table to zero rows

##### Arguments
* **string** `$table` - Name of the table to be truncated

##### Exceptions thrown
###### \Exception
* In case of database errors

### MySQLie_stmt
#### fetch_assoc
    MySQLie_stmt::fetch_assoc()

Fetch the result as an associative array

##### Return value
* **array** - An array of result rows

##### Exceptions thrown
###### \Exception
* In case of database errors

#### fetch_first
    MySQLie_stmt::fetch_first()

Fetch the first row of the statement and return the result as an associative array

##### Return value
* **array** - A result row as an associative array

##### Exceptions thrown
###### \Exception
* In case of database errors

