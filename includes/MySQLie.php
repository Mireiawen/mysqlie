<?php
declare(strict_types = 1);

namespace Mireiawen\MySQLie;

use mysqli;

/**
 * Extend the MySQLi class with custom functionality
 */
class MySQLie extends mysqli
{
	/**
	 * Construct the MySQLi class and set the
	 * connection character set
	 *
	 * @param string $database
	 *    The database name to use
	 * @param string $username
	 *    The account to log in with
	 * @param string $password
	 *    The password to log in with
	 * @param string $hostname
	 *    The database host to connect to
	 * @param string $charset
	 *    The connection character set to use
	 *
	 * @throws \Exception
	 *    On connection errors
	 */
	public function __construct(string $database, string $username, string $password, string $hostname, string $charset = 'utf8')
	{
		// Check for MySQLi support
		if (!\extension_loaded('mysqli'))
		{
			throw new \Exception(\_('MySQLi extension is required!'));
		}
		
		parent::__construct($hostname, $username, $password, $database);
		if ($this->errno)
		{
			throw new \Exception(\sprintf(\_('Unable to connect to database: %s'), $this->connect_error));
		}
		
		if (!$this->set_charset($charset))
		{
			throw new \Exception(\sprintf(\_('Unable to set character set: %s'), $this->error));
		}
	}
	
	/**
	 * Truncates the given table to zero rows
	 *
	 * @param string $table
	 *    Name of the table to be truncated
	 *
	 * @throws \Exception
	 *  On database errors
	 */
	public function truncate(string $table) : void
	{
		// Prepare the query
		$sql = \sprintf('TRUNCATE TABLE %s', $this->escape_identifier($table));
		$stmt = $this->prepare($sql);
		if ($stmt === FALSE)
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $this->error));
		}
		
		// Execute it
		if (!$stmt->execute())
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $stmt->error));
		}
	}
	
	/**
	 * Set the foreign key checks on or off
	 *
	 * @param bool $state
	 *    TRUE to turn foreign key checks on
	 *    FALSE to turn foreign key checks off,,
	 *
	 * @throws \Exception
	 *  On database errors
	 */
	public function foreign_key_checks(bool $state) : void
	{
		// Prepare the query
		$sql = 'SET FOREIGN_KEY_CHECKS = ?';
		$stmt = $this->prepare($sql);
		if ($stmt === FALSE)
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $this->error));
		}
		
		
		// Bind the parameters
		$s = \intval($state);
		if (!$stmt->bind_param('i', $s))
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $stmt->error));
		}
		
		// Execute it
		if (!$stmt->execute())
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $stmt->error));
		}
	}
	
	/**
	 * Get the table auto increment value
	 *
	 * @param string $table
	 *    Name of the table to get the auto increment from
	 *
	 * @return int
	 *    The auto increment value
	 *
	 * @throws \Exception
	 *  On database errors
	 */
	public function get_autoincrement(string $table) : int
	{
		// Prepare the query
		$sql = \sprintf('SELECT %s FROM %s.%s WHERE %s = ? AND %s = ?',
			$this->escape_identifier('AUTO_INCREMENT'),
			$this->escape_identifier('information_schema'),
			$this->escape_identifier('tables'),
			$this->escape_identifier('table_name'),
			$this->escape_identifier('table_schema')
		);
		$stmt = $this->prepare($sql);
		if ($stmt === FALSE)
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $this->error));
		}
		
		// Bind the parameters
		$schema = \get_class($this);
		if (!$stmt->bind_param('ss', $table, $schema))
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $stmt->error));
		}
		
		// Execute the query
		$row = $this->fetch_first($stmt);
		if (isset($row['AUTO_INCREMENT']))
		{
			return $row['AUTO_INCREMENT'];
		}
		
		throw new \Exception(\sprintf(\_('AUTO_INCREMENT was not set for %s'), $table));
	}
	
	/**
	 * Fetch the first row of the statement and return
	 * the result as an associative array
	 *
	 * @param \mysqli_stmt $stmt
	 *    A prepared MySQLi statement
	 *
	 * @return array
	 *    A result row as an associative array
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function fetch_first(\mysqli_stmt $stmt) : array
	{
		// Execute the query itself
		if (!$stmt->execute())
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $stmt->error));
		}
		
		// Get result
		if (!$res = $stmt->get_result())
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $stmt->error));
		}
		
		// Get first row
		$row = $res->fetch_assoc();
		
		// Free the result
		$res->free();
		
		// Return the row
		return $row;
	}
	
	/**
	 * @brief Fetch the result as an associative array
	 *
	 * Fetch all the result rows of the stmt and return
	 * those as associative array
	 *
	 * @param \mysqli_stmt $stmt
	 *    A prepared MySQLi statement
	 *
	 * @return array
	 *    An array of result rows
	 *
	 * @throws \Exception
	 *  On database errors
	 */
	public function fetch_assoc(\mysqli_stmt $stmt) : array
	{
		// Execute the query itself
		if (!$stmt->execute())
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $stmt->error));
		}
		
		// Get result
		if (!$res = $stmt->get_result())
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $stmt->error));
		}
		
		// Get all rows
		$rows = [];
		while (TRUE)
		{
			$row = $res->fetch_assoc();
			if ($row === NULL)
			{
				break;
			}
			$rows[] = $row;
		}
		
		// Free the result and return rows
		$res->free();
		return $rows;
	}
	
	/**
	 * Get the current status autocommit status
	 * from the database
	 *
	 * @return bool
	 *    The autocommit status; TRUE if
	 *    it is on, FALSE if it is off
	 *
	 * @throws \Exception
	 *  On database errors
	 */
	public function get_autocommit() : bool
	{
		// Prepare the query
		$sql = 'SELECT @@autocommit';
		$stmt = $this->prepare($sql);
		if ($stmt === FALSE)
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $this->error));
		}
		
		// Get the actual result
		$row = $this->fetch_first($stmt);
		
		// Return the status
		if (isset($row['@@autocommit']))
		{
			return (bool)($row['@@autocommit']);
		}
		
		// Status was not known, try to set it
		if (!$this->autocommit(TRUE))
		{
			throw new \Exception(\sprintf(_('Unable to execute database query: %s'), $this->error));
		}
		return TRUE;
	}
	
	/**
	 * Escape an identifier name for the SQL query
	 *
	 * @param string $name
	 *  The identifier to escape
	 *
	 * @return string
	 *    The escaped string
	 *
	 * @todo Is there better way to do this, like querying database for escape char?
	 */
	public function escape_identifier(string $name) : string
	{
		return \sprintf('`%s`', $name);
	}
}
