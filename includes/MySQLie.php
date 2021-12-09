<?php
declare(strict_types = 1);

namespace Mireiawen\MySQLie;

use JetBrains\PhpStorm\Pure;
use mysqli;

/**
 * Extend the MySQLi class with custom functionality
 *
 * @package Mireiawen\MySQLie
 */
class MySQLie extends mysqli
{
	/**
	 * Open a new connection to the MySQL server
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
	 * @throws \RuntimeException
	 *    In case the required extensions are not loaded
	 *
	 * @throws \mysqli_sql_exception
	 *    On database connection errors
	 */
	public function __construct(string $database, string $username, string $password, string $hostname, string $charset = 'utf8')
	{
		// Check for translation support
		if (!\extension_loaded('gettext'))
		{
			throw new \RuntimeException('gettext extension is required!');
		}
		
		// Check for MySQLi support
		if (!\extension_loaded('mysqli'))
		{
			throw new \RuntimeException(\_('MySQLi extension is required!'));
		}
		
		// Set up the MySQLi error reporting
		\mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		
		/**
		 * @link  https://www.php.net/manual/en/mysqli.construct.php
		 *
		 * Object-oriented style only:
		 * If the connection fails, an object is still returned. To check
		 * whether the connection failed, use either the mysqli_connect_error()
		 * function or the mysqli->connect_error property as in the preceding
		 * examples.
		 */
		@parent::__construct($hostname, $username, $password, $database);
		if ($this->connect_errno)
		{
			throw new \mysqli_sql_exception(\sprintf(\_('Unable to connect to database: %s'), $this->connect_error));
		}
		
		// Set the initial charset for connection
		$this->set_charset($charset);
	}
	
	/**
	 * Prepare an SQL statement for execution
	 *
	 * @param string $query
	 *    The query, as a string
	 *
	 * @return MySQLie_stmt
	 *    Prepared statement object
	 *
	 * @throws \mysqli_sql_exception
	 *    On database errors
	 *
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public function prepare($query) : MySQLie_stmt
	{
		return new MySQLie_stmt($this, $query);
	}
	
	/**
	 * Constructs a new mysqli_stmt object
	 *
	 * @return MySQLie_stmt
	 *    The initialized statement object
	 *
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public function stmt_init() : MySQLie_stmt
	{
		return new MySQLie_stmt($this, NULL);
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
	
	/**
	 * Escape the identifiers in the SQL query
	 *
	 * @param string $sql
	 *    The query to escape
	 *
	 * @param string[] $identifiers
	 *    The identifier list
	 *
	 * @return string
	 *    The escaped query
	 */
	#[Pure]
	public function escape_query_identifiers(string $sql, array $identifiers) : string
	{
		foreach ($identifiers as &$identifier)
		{
			$identifier = $this->escape_identifier($identifier);
		}
		
		return \vsprintf($sql, $identifiers);
	}
	
	/**
	 * Set the foreign key checks on or off
	 *
	 * @param bool $state
	 *    TRUE to turn foreign key checks on
	 *    FALSE to turn foreign key checks off
	 *
	 * @throws \mysqli_sql_exception
	 *  On database errors
	 */
	public function foreign_key_checks(bool $state) : void
	{
		// Prepare the query
		$sql = 'SET FOREIGN_KEY_CHECKS = ?';
		$stmt = $this->prepare($sql);
		
		// Bind the parameters
		$s = (int)$state;
		$stmt->bind_param('i', $s);
		
		// Execute it
		$stmt->execute();
	}
	
	/**
	 * Get the current status autocommit status from the database
	 *
	 * @return bool
	 *    The autocommit status; TRUE if
	 *    it is on, FALSE if it is off
	 *
	 * @throws \mysqli_sql_exception
	 *  On database errors
	 */
	public function get_autocommit() : bool
	{
		// Prepare the query
		$sql = 'SELECT @@autocommit';
		$stmt = $this->prepare($sql);
		
		// Get the actual result
		$row = $stmt->fetch_first();
		
		// Return the status
		if (isset($row['@@autocommit']))
		{
			return (bool)($row['@@autocommit']);
		}
		
		// Status was not known, try to set it
		$this->autocommit(TRUE);
		
		return TRUE;
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
	 * @throws \mysqli_sql_exception
	 *  On database errors
	 */
	public function get_autoincrement(string $table) : int
	{
		// Prepare the query
		$sql = $this->escape_query_identifiers(
			'SELECT %s FROM %s.%s WHERE %s = ? AND %s = ?',
			['AUTO_INCREMENT', 'information_schema', 'tables', 'table_name', 'table_schema']
		);
		$stmt = $this->prepare($sql);
		
		// Bind the parameters
		$schema = \get_class($this);
		$stmt->bind_param('ss', $table, $schema);
		
		// Execute the query
		$row = $stmt->fetch_first();
		if (isset($row['AUTO_INCREMENT']))
		{
			return $row['AUTO_INCREMENT'];
		}
		
		throw new \mysqli_sql_exception(\sprintf(\_('AUTO_INCREMENT was not set for %s'), $table));
	}
	
	/**
	 * Truncates the given table to zero rows
	 *
	 * @param string $table
	 *    Name of the table to be truncated
	 *
	 * @throws \mysqli_sql_exception
	 *  On database errors
	 */
	public function truncate(string $table) : void
	{
		// Prepare the query
		$sql = $this->escape_query_identifiers('TRUNCATE TABLE %s', [$table]);
		$stmt = $this->prepare($sql);
		
		// Execute it
		$stmt->execute();
	}
}
