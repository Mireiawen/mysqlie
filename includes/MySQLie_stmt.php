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
	/**
	 * Fetch the result as an associative array
	 *
	 * @return array
	 *    An array of result rows
	 *
	 * @throws \mysqli_sql_exception
	 *  On database errors
	 */
	public function fetch_assoc() : array
	{
		// Execute the query itself
		$this->execute();
		
		// Get result
		$result = $this->get_result();
		
		if ($result === FALSE)
		{
			return [];
		}
		
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
	 * @throws \mysqli_sql_exception
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
}