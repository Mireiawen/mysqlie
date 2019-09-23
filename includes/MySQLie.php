<?php
declare(strict_types = 1);

namespace Mireiawen\MySQLie;

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
		
		@parent::__construct($hostname, $username, $password, $database);
		if ($this->connect_errno)
		{
			throw new \Exception(\sprintf(\_('Unable to connect to database: %s'), $this->connect_error));
		}
		
		$this->set_charset($charset);
	}
	
	/* ************************************************************************
	 * Custom methods begin here
	 * ************************************************************************/
	
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
		
		// Bind the parameters
		$s = \intval($state);
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
	 * @throws \Exception
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
	 * @throws \Exception
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
		
		throw new \Exception(\sprintf(\_('AUTO_INCREMENT was not set for %s'), $table));
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
		$sql = $this->escape_query_identifiers('TRUNCATE TABLE %s', [$table]);
		$stmt = $this->prepare($sql);
		
		// Execute it
		$stmt->execute();
	}
	
	/* ************************************************************************
	 * Overloaded methods begin here
	 * ************************************************************************/
	
	/**
	 * Turns on or off auto-committing database modifications
	 *
	 * @param bool $mode
	 *    Whether to turn on auto-commit or not
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function autocommit($mode) : void
	{
		if (!parent::autocommit($mode))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Starts a transaction
	 *
	 * @param int $flags
	 *    See https://www.php.net/manual/mysqli.begin-transaction.php
	 *
	 * @param string|null $name
	 *    Savepoint name for the transaction
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function begin_transaction($flags = NULL, $name = NULL) : void
	{
		if (!parent::begin_transaction($flags, $name))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Changes the user of the specified database connection
	 *
	 * @param string $user
	 *    The MySQL user name
	 *
	 * @param string $password
	 *    The MySQL password
	 *
	 * @param string $database
	 *    The database to change to
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function change_user($user, $password, $database) : void
	{
		if (!parent::change_user($user, $password, $database))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Closes a previously opened database connection
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
	 * Commits the current transaction
	 *
	 * @param int $flags
	 *    A bitmask of MYSQLI_TRANS_COR_* constants
	 *
	 * @param string|NULL $name
	 *    If provided then COMMIT/$name/ is executed
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function commit($flags = NULL, $name = NULL) : void
	{
		if (!parent::commit($flags, $name))
		{
			$this->throw_error();
		};
	}
	
	/**
	 * Returns statistics about the client connection
	 *
	 * @return array
	 *    Array with connection stats
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function get_connection_stats() : array
	{
		$result = parent::get_connection_stats();
		if ($result === FALSE)
		{
			$this->throw_error();
		}
		
		return $result;
	}
	
	/**
	 * Asks the server to kill a MySQL thread
	 *
	 * @param int $processid
	 *    MySQL thread to kill
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function kill($processid) : void
	{
		if (!parent::kill($processid))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Prepare next result from multi_query
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
	 * Set options
	 *
	 * @param int $option
	 *    The option that you want to set
	 *
	 * @param mixed $value
	 *    The value for the option
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function options($option, $value) : void
	{
		if (parent::options($option, $value))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Pings a server connection
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function ping() : void
	{
		if (!parent::ping())
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Poll connections
	 *
	 * @param array $read
	 *    List of connections to check for outstanding results that can be read
	 *
	 * @param array $error
	 *    List of connections on which an error occurred, for example, query failure or lost connection
	 *
	 * @param array $reject
	 *    List of connections rejected because no asynchronous query has been run on for which the function could poll results
	 *
	 * @param int $sec
	 *    Maximum number of seconds to wait, must be non-negative
	 *
	 * @param int $usec
	 *    Maximum number of microseconds to wait, must be non-negative
	 *
	 * @return int
	 *    Returns number of ready connections
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public static function poll(&$read, &$error, &$reject, $sec, $usec = 0) : int
	{
		$result = parent::poll($read, $error, $reject, $sec, $usec);
		if ($result === FALSE)
		{
			throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), _('Database polling failed')));
		}
		
		return $result;
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
	 * @throws \Exception
	 *    On database errors
	 */
	public function prepare($query) : MySQLie_stmt
	{
		$stmt = new MySQLie_stmt($this, $query);
		if ($stmt === FALSE)
		{
			$this->throw_error();
		}
		
		return $stmt;
	}
	
	/**
	 * @param string $query
	 * @param int $resultmode
	 *
	 * @return \mysqli_result|null
	 * @throws \Exception
	 *    On database errors
	 */
	public function query($query, $resultmode = MYSQLI_STORE_RESULT) : ?\mysqli_result
	{
		$result = parent::query($query, $resultmode);
		
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
	 * Opens a connection to a mysql server
	 *
	 * @param string|null $host
	 *    Can be either a host name or an IP address. Passing the NULL value or the string "localhost" to this parameter, the local host is assumed
	 *
	 * @param string|null $username
	 *    The MySQL user name
	 *
	 * @param string|null $passwd
	 *    If provided or NULL, the MySQL server will attempt to authenticate the user against those user records which have no password only
	 *
	 * @param string|null $dbname
	 *    If provided will specify the default database to be used when performing queries
	 *
	 * @param int|null $port
	 *    Specifies the port number to attempt to connect to the MySQL server
	 *
	 * @param string|null $socket
	 *    Specifies the socket or named pipe that should be used
	 *
	 * @param int|null $flags
	 *    With the parameter flags you can set different connection options
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function real_connect($host = NULL, $username = NULL, $passwd = NULL, $dbname = NULL, $port = NULL, $socket = NULL, $flags = NULL) : void
	{
		if (!parent::real_connect($host, $username, $passwd, $dbname, $port, $socket, $flags))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Escapes special characters in a string for use in an SQL statement, taking into account the current charset of the connection
	 *
	 * @param string $escapestr
	 *    The string to be escaped
	 *
	 * @return string
	 *    The escaped string
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function real_escape_string($escapestr) : string
	{
		$result = @parent::real_escape_string($escapestr);
		if ($result === NULL)
		{
			$this->throw_error();
		}
		
		return $result;
	}
	
	/**
	 * Execute an SQL query
	 *
	 * @param string $query
	 *    The query, as a string
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function real_query($query) : void
	{
		if (!parent::real_query($query))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Get result from async query
	 *
	 * @return \mysqli_result
	 *    The query result
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function reap_async_query() : \mysqli_result
	{
		$result = parent::reap_async_query();
		if ($result === FALSE)
		{
			$this->throw_error();
		}
		
		return $result;
	}
	
	/**
	 * Flushes tables or caches, or resets the replication server information
	 *
	 * @param int $options
	 *    The options to refresh, using the MYSQLI_REFRESH_* constants
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function refresh($options) : void
	{
		/** @noinspection PhpVoidFunctionResultUsedInspection */
		if (!parent::refresh($options))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Removes the named savepoint from the set of savepoints of the current transaction
	 *
	 * @param string $name
	 *    Name of the savepoint to remove
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function release_savepoint($name) : void
	{
		if (!parent::release_savepoint($name))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Rolls back current transaction
	 *
	 * @param int $flags
	 *    A bitmask of MYSQLI_TRANS_COR_* constants
	 *
	 * @param string|null $name
	 *    If provided then ROLLBACK/$name/ is executed
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function rollback($flags = 0, $name = NULL) : void
	{
		if (!parent::rollback($flags, $name))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Set a named transaction savepoint
	 *
	 * @param string $name
	 *    Name of the savepoint
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function savepoint($name) : void
	{
		if (!parent::savepoint($name))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Selects the default database for database queries
	 *
	 * @param string $dbname
	 *    The database name.
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function select_db($dbname) : void
	{
		if (!parent::select_db($dbname))
		{
			$this->throw_error();
		}
	}
	
	/**
	 * Sets the default client character set
	 *
	 * @param string $charset
	 *    The charset to be set as default
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function set_charset($charset) : void
	{
		if (!parent::set_charset($charset))
		{
			throw new \Exception(\sprintf(\_('Unable to set character set: %s'), $this->error));
		}
	}
	
	/**
	 * Gets the current system status
	 *
	 * @return string
	 *    A string describing the server status
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function stat() : string
	{
		$result = parent::stat();
		if ($result === FALSE)
		{
			$this->throw_error();
		}
		
		return $result;
	}
	
	/**
	 * Constructs a new mysqli_stmt object
	 *
	 * @return MySQLie_stmt
	 *    The initialized statement object
	 */
	public function stmt_init() : MySQLie_stmt
	{
		$stmt = new MySQLie_stmt($this, NULL);
		return $stmt;
	}
	
	/**
	 * Transfers a result set from the last query
	 *
	 * @param int $option
	 *    The option that you want to set
	 *
	 * @return \mysqli_result|null
	 *    The buffered result object
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function store_result($option = NULL) : ?\mysqli_result
	{
		$result = parent::store_result();
		
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
	 * Initiate a result set retrieval
	 *
	 * @return \mysqli_result
	 *    The buffered result object
	 *
	 * @throws \Exception
	 *    On database errors
	 */
	public function use_result() : \mysqli_result
	{
		$result = parent::use_result();
		
		if ($result === FALSE)
		{
			$this->throw_error();
		}
		
		return $result;
	}
	
	/**
	 * Throw the database error
	 *
	 * @throws \Exception
	 */
	protected function throw_error() : void
	{
		throw new \Exception(\sprintf(\_('Unable to execute database query: %s'), $this->error));
	}
}
