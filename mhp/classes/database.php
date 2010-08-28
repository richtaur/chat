<?php

/**
 * Database constructor
 */
class Database extends Core {

	protected
		$connection,
		$debug,
		$debug_func;

	/**
	 * Constructor method. Connects to the database
	 * @public
	 */
    function __construct() {

		$this->debug = Array();
		$this->debug_func = 'debugNone';

		parent::__construct();

    }

	/**
	 * Gets the number of affected rows from the last query
	 * @return integer Number of affected rows
	 * @public
	 */
    function affectedRows() {
        return mysql_affected_rows($this->connection);
    }

	/**
	 * Checks a table to see if a key/value pair exists
	 * @param string table The table to check
	 * @param string field The field in the table
	 * @param string value The value of the field to check
	 * @return bool Returns true if it found a matching row
	 * @public
	 */
    function checkField($table, $field, $value, $and = '') {

		$qry = "
			SELECT
				$field
			FROM
				$table
			WHERE
				$field = '$value'
				$and
		";

        $res = $this->query($qry);
		$row = $this->fetchRow($res);

        return ($row ? true : false);

    }

	/**
	 * Cleans a value for the DB
	 * @param str $value The value to clean
	 * @return str The cleaned string
	 */
	static function clean($value) {

		$value = stripslashes($value);
		$value = trim($value);
		$value = str_replace("'", "\'", $value);

		return $value;

	}

	/**
	 * Connect to the database
	 * @param string db_server The database server to connect to (often localhost)
	 * @param string db_user The user to send to the server
	 * @param string db_password The user's password
	 * @param string db_name The name of the database to use
	 * @public
	 */
    function connect($db_server, $db_user, $db_password, $db_name) {

        $this->connection = mysql_connect($db_server, $db_user, $db_password);
        $this->select($db_name, $this->connection);

        return ($this->connection ? true : false);
        
    }

	function debugNone() {}

	/**
	 * Adds a debug message to $debug
	 * @param str The debug message
	 * @public
	 */
	function debugPut($debug) {
		$this->debug[] = $debug;
	}

	/**
	 * Deletes rows from the database
	 * @param string table The table to query
	 * @param string where There WHERE clause in the query
	 * @param integer limit How many rows to limit (default is 1)
	 * @return integer Number of affected rows
	 * @public
	 */
    function deleteRows($table, $where, $limit = 1) {

		$limit = ($limit ? 'LIMIT ' . $limit : '');

        $qry = "
			DELETE FROM
				$table
			WHERE
				$where
			$limit
		";

        $this->query($qry);

		return $this->affectedRows();

    }
     
	/**
	 * Retrieves a row from the database
	 * @param string result The result set returned from the query
	 * @return array Associatve index of the retrieved row
	 */
    function fetchRow($res) {
        return @mysql_fetch_assoc($res);
    }

	/**
	 * Takes a prepared query, parses it, and returns the total count of the rows
	 * @param string qry The query to parse
	 * @param select The (optional) specific table/fields to count [default: *]
	 * @return int The total number of rows
	 */
	function getQueryCount($qry, $select = '*') {

		$field = 'num_rows';

		// Rip out the LIMIT if it's there
		$pos = strpos($qry, 'LIMIT');

		if ($pos) {
			$qry = substr($qry, 0, $pos);
		}

		// Rip out the SELECT --> FROM
		$pos = strpos($qry, 'FROM');
		$qry = substr($qry, $pos, strlen($qry));

		// Rip out GROUP BY if it's there
		$pos = strpos($qry, 'GROUP');

		if ($pos) {

			$end = strpos($qry, 'ORDER');

			if ($end) {
				$qry = substr($qry, 0, $pos) . substr($qry, $end, (strlen($qry)));
			}
			
		}

		// Insert our new selection
		$qry = "SELECT COUNT($select) AS $field $qry";
		$res = $this->getRow($qry);

		return $res[$field];

	}

	/**
	 * Get a single row from the database
	 * @param qry The query to send
	 * @return array Associative array of the row
	 */
	function getRow($qry) {

		$res = $this->query($qry);
		$row = $this->fetchRow($res);

		return $row;

	}

	/**
	 * Retreive rows from the database
	 * @param qry The query to get the rows
	 * @param array Numeric array of the rows
	 */
	function getRows($qry) {

		$rows = Array();
		$res  = $this->query($qry);

		while ($row = $this->fetchRow($res)) {
			$rows[] = $row;
		}

		return $rows;

	}

	/**
	 * Format a query for insertion into the database
	 * @param string table The table to insert into
	 * @param array pairs Key value pairs of the fields and values to insert
	 * @return string The prepared query, ready to insert
	 */
    function formatInsert($table, $pairs) {

        foreach ($pairs as $field => $value) {

			$fields .= $field . ',';

			if (!is_numeric($value)) {
				$value = self::clean($value);
			}

			$values .= "'$value',";

        }

		$fields = substr($fields, 0, -1);
		$values = substr($values, 0, -1);

		$qry = "
			INSERT INTO
				$table
			($fields)
			VALUES
				($values)
		";

        return $qry;

    }

	/**
	 * Formats an update query based on a key/value array
	 * @param string table The name of the table
	 * @param array pairs The key/value pair of the fields/values
	 * @param string where The WHERE clause of the array (default: 1)
	 * @param integer limit The limit on tbe query
	 * @return string The prepared query
	*/
    function formatUpdate($table, $pairs, $where = '1', $limit = false) {

        foreach ($pairs as $key => $value) {

			if (!is_numeric($value)) {
				$value = self::clean($value);
			}

			$set .= "$key = '$value', ";

        }

        $set   = substr($set, 0, -2);
		$limit = ($limit ? ' LIMIT ' . $limit : '');

		$qry = "
			UPDATE
				$table
			SET
				$set
			WHERE
				$where
			$limit
		";

        return $qry;

    }

	/**
	 * Get the id of the last inserted row
	 * @return integer They id (if it exists) of the last inserted row
	*/
    function getId() {
        return mysql_insert_id($this->connection);
    }

	/**
	 * Inserts rows into the database
	 * @param string table The table to send the query to
	 * @param array pairs The key/value pairs to insert into the database
	 * @return integer Number of affected rows
	*/
	function insertRow($table, $pairs) {
		return $this->query($this->formatInsert($table, $pairs));
	}

	/**
	 * Gets the number of rows from a result set
	 * @return integer Number of rows
	*/
    function numRows($res) {
        return mysql_num_rows($res);
    }

	/**
	 * Send a query to the database
	 * @param string qry The query to send
	 * @return string A reference to the result set
	*/
    function query($qry) {

		$func = $this->debug_func;
		$this->$func($qry);

        return mysql_query($qry, $this->connection);

    }

	/**
	 * Select a database on the server
	 * @param string db_name The name of the database
	 * @return bool Returns true on success and false on failure
	*/
    function select($db_name, $connection = null) {

		if ($connection) {
			$this->connection = &$connection;
		}

        return mysql_select_db($db_name, $this->connection);

    }

	/**
	 * Turns on or off the debug option
	 * @param bool debug The new value of the debug option
	*/
	function setDebug($debug) {
		if ($debug) {
			$this->debug_func = 'debugPut';
		} else {
			$this->debug_func = 'debugNone';
		}
	}

	/**
	 * Updates rows in the database
	 * @param string table The table to send the query to
	 * @param array pairs The key/value pairs to insert into the database
	 * @param string where The WHERE clause of the array (default: 1)
	 * @param integer limit The limit on tbe query
	 * @return integer Number of affected rows
	*/
	function updateRows($table, $pairs, $where = '1', $limit = false) {
		return $this->query($this->formatUpdate($table, $pairs, $where, $limit));
	}
        
}
