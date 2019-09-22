<?php
declare(strict_types = 1);

namespace Mireiawen\MySQLie;

use mysqli_stmt;

/**
 * Extend the MySQLi_stmt class with custom functionality
 *
 * @package Mireiawen\MySQLie
 */
class MySQLie_stmt extends mysqli_stmt
{
	/* ************************************************************************
	 * Custom methods begin here
	 * ************************************************************************/
	
	/**
	 * Fetch the result as an associative array
	 *
	 * @return array
	 *    An array of result rows
	 *
	 * @throws \Exception
	 *  On database errors
	 */
	public function fetch_assoc() : array
	{
		// Execute the query itself
		$this->execute();
		
		// Get result
		$result = $this->get_result();
		
		// Get all rows
		$rows = $result->fetch_all(\MYSQLI_ASSOC);
		
		// Free the result and return the rows
		$result->free();
		return $rows;
	}
	
	/**
	 * Fetch the first row of the statement and return the result as an
	 * associative array
	 *
	 * @return array
	 *    A result row as an associative array
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function fetch_first() : array
	{
		// Execute the query itself
		$this->execute();
		
		// Get result
		$result = $this->get_result();
		
		// Get first row
		$row = $result->fetch_assoc();
		
		// Free the result and return the row
		$result->free();
		return $row;
	}
	
	/* ************************************************************************
	 * Overloaded methods begin here
	 * ************************************************************************/
	
	/**
	 * Used to get the current value of a statement attribute
	 *
	 * @param int $attr
	 *    The attribute that you want to get
	 *
	 * @return int
	 *    Value of the attribute
	 *
	 * @throws \Exception
	 *    If $attr is not found
	 *
	 * @noinspection PhpSignatureMismatchDuringInheritanceInspection
	 */
	public function attr_get(int $attr) : int
	{
		$value = parent::attr_get($attr);
		if ($value === FALSE)
		{
			throw new \Exception(\sprintf(\_("The attr %d was not found"), $attr));
		}
		
		return $value;
	}
	
	/**
	 * Binds variables to a prepared statement as parameters
	 *
	 * @param string $types
	 *     string that contains one or more characters which specify the types for the corresponding bind variables
	 *
	 * @param mixed $var1
	 * @param mixed ...$_
	 *    The number of variables and length of string types must match the parameters in the statement
	 *
	 * @throws \Exception
	 *    On database errors
	 *
	 * @noinspection PhpSignatureMismatchDuringInheritanceInspection
	 */
	public function bind_param(string $types, &$var1, &...$_) : void
	{
		if (!parent::bind_param($types, $var1, $_))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Binds variables to a prepared statement for result storage
	 *
	 * @param mixed $var1
	 * @param mixed ...$_
	 *    The variable to be bound
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function bind_result(&$var1, &...$_) : void
	{
		if (!parent::bind_result($var1, $_))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Closes a prepared statement
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function close() : void
	{
		if (!parent::close())
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Executes a prepared Query
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function execute() : void
	{
		if (!parent::execute())
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Fetch results from a prepared statement into the bound variables
	 *
	 * @return bool|null
	 *    TRUE    Success. Data has been fetched
	 *    NULL    No more rows/data exists or data truncation occurred
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function fetch() : ?bool
	{
		$result = parent::fetch();
		if ($result === FALSE)
		{
			$this->throw_error();
		}
		
		return $result;
	}
	
	/**
	 * Gets a result set from a prepared statement
	 *
	 * @return \mysqli_result|null
	 *    Result set for successful SELECT queries, or FALSE for other DML queries
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function get_result() : ?\mysqli_result
	{
		$result = parent::get_result();
		
		// Check for error
		if ($result === FALSE && $this->errno !== 0)
		{
			$this->throw_error();
		}
		
		if ($result === FALSE)
		{
			return NULL;
		}
		
		return $result;
	}
	
	/**
	 * Reads the next result from a multiple query
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function next_result() : void
	{
		if (!parent::next_result())
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Prepare an SQL statement for execution
	 *
	 * @param string $query
	 *    The query, as a string. It must consist of a single SQL statement
	 *
	 * @throws \Exception
	 *    On database errors
	 *
	 * @noinspection PhpSignatureMismatchDuringInheritanceInspection
	 */
	public function prepare(string $query) : void
	{
		if (!parent::prepare($query))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Resets a prepared statement
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function reset() : void
	{
		if (!parent::reset())
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Returns result set metadata from a prepared statement
	 *
	 * @return \mysqli_result|null
	 *    Result object
	 * @throws \Exception
	 *    On database errors
	 */
	public function result_metadata() : ?\mysqli_result
	{
		$result = parent::result_metadata();
		if ($result === FALSE && $this->errno !== 0)
		{
			$this->throw_error();
		}
		
		if ($result === FALSE)
		{
			return NULL;
		}
		
		return $result;
	}
	
	/**
	 * Send data in blocks
	 *
	 * @param int $param_nr
	 *    Indicates which parameter to associate the data with. Parameters are numbered beginning with 0
	 *
	 * @param string $data
	 *    A string containing data to be sent
	 *
	 * @throws \Exception
	 *    On database errors
	 *
	 * @noinspection PhpSignatureMismatchDuringInheritanceInspection
	 */
	public function send_long_data(int $param_nr, string $data) : void
	{
		if (!parent::send_long_data($param_nr, $data))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Transfers a result set from a prepared statement
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function store_result() : void
	{
		if (!parent::store_result())
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Throw the database error
	 *
	 * @throws \Exception
	 */
	protected function throw_error()
	{
		throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $this->error));
	}
}