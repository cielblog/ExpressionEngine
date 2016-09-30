<?php

namespace EllisLab\ExpressionEngine\Service\Database\Backup;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Database Backup Class
 *
 * @package		ExpressionEngine
 * @subpackage	Database
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Query {

	/**
	 * @var Database\Query Database Query object
	 */
	protected $query;

	/**
	 * @var boolean When TRUE, class returns queries with no linebreaks
	 */
	protected $compact_queries = FALSE;

	/**
	 * @var array Array of tables and their corresponding row counts and size on disk
	 */
	protected $tables = [];

	/**
	 * @var int Number of bytes to limit INSERT query sizes to
	 */
	protected $query_size_limit = 3e+6;

	/**
	 * Constructor
	 *
	 * @param	Database\Query	$query	Database query object
	 */
	public function __construct(\EllisLab\ExpressionEngine\Service\Database\Query $query)
	{
		$this->query = $query;
	}

	/**
	 * Makes the class return pretty queries with helpful whitespace formatting
	 */
	public function makePrettyQueries()
	{
		$this->compact_queries = FALSE;
	}

	/**
	 * Makes the class return queries that have no linebreaks in them
	 */
	public function makeCompactQueries()
	{
		$this->compact_queries = TRUE;
	}

	/**
	 * Sets the byte limit for INSERT query sizes
	 *
	 * @param	int	$limit	Number of bytes
	 */
	public function setQuerySizeLimit($limit)
	{
		$this->query_size_limit = $limit;
	}

	/**
	 * Returns an array of names of tables present in the database
	 *
	 * @return	array	Associative array of tables to row count and size on disk, e.g.:
	 *	[
	 *		'table' => [
	 *			'rows' => 123,
	 *			'size' => 123456
	 *		],
	 *		...
	 *	]
	 */
	public function getTables()
	{
		if (empty($this->tables))
		{
			$query = $this->query
				->query(sprintf('SHOW TABLE STATUS FROM `%s`', $this->query->database));

			foreach ($query->result() as $row)
			{
				$this->tables[$row->Name] = [
					'rows' => $row->Rows,
					'size' => $row->Data_length
				];
			}
		}

		return $this->tables;
	}

	/**
	 * Given a table name, generates a CREATE TABLE statement for it
	 *
	 * @param	string	$table_name	Table name
	 * @return	string	CREATE TABLE statement for the given table
	 */
	public function getCreateForTable($table_name)
	{
		$create_result = $this->query
			->query(sprintf('SHOW CREATE TABLE `%s`;', $table_name))
			->row_array();

		if ( ! isset($create_result['Create Table']))
		{
			throw new Exception('Could not generate CREATE TABLE statement for table ' . $table_name, 1);
		}

		$create = $create_result['Create Table'] . ';';

		if ($this->compact_queries)
		{
			$create = str_replace("\n", '', $create);
		}

		return $create;
	}

	/**
	 * Given a table name, generates a DROP TABLE IF EXISTS statement for it
	 *
	 * @param	string	$table_name	Table name
	 * @return	string	DROP TABLE IF EXISTS statement for the given table
	 */
	public function getDropStatement($table_name)
	{
		return sprintf('DROP TABLE IF EXISTS `%s`;', $table_name);
	}

	/**
	 * Given a table name, queries for and caches the total rows for the table
	 *
	 * @param	string	$table_name	Table name
	 * @return	int		Total rows in table
	 */
	public function getTotalRows($table_name)
	{
		$tables = $this->getTables();

		if (isset($tables[$table_name]))
		{
			return $tables[$table_name]['rows'];
		}

		throw new Exception('Not existent table requested: ' . $table_name, 1);
	}

	/**
	 * Queries for data given a table name and offset parameters, and generates
	 * an array of values for each row to follow a VALUES statement
	 *
	 * @param	string	$table_name		Table name
	 * @param	int		$offset			Query offset
	 * @param	int		$limit			Query limit
	 * @return	array	Array containing ull, valid INSERT INTO statement for a given
	 * range of table data, and also the number of rows that were exported, e.g.:
	 *	[
	 *		'insert_string' => 'INSERT INTO `table_name` VALUES ... ;',
	 *		'rows_exported' => 50
	 *	]
	 */
	public function getInsertsForTable($table_name, $offset, $limit)
	{
		$data = $this->query
			->query(sprintf('DESCRIBE `%s`;', $table_name))
			->result_array();

		// Surround fields with backticks
		$fields = array_map(function($row)
		{
			return sprintf('`%s`', $row['Field']);
		}, $data);

		$insert_prepend = sprintf('INSERT INTO `%s` (%s) VALUES ', $table_name, implode(', ', $fields));

		$rows = $this->getValuesForTable($table_name, $offset, $limit);
		$row_chunks = $this->makeRowChunks($rows);

		$inserts = '';
		foreach ($row_chunks as $row_chunk)
		{
			if ($this->compact_queries)
			{
				$inserts .= $insert_prepend . implode(', ', $row_chunk);
			}
			else
			{
				$inserts .= $insert_prepend . "\n\t" . implode(",\n\t", $row_chunk);
			}

			$inserts .=  ";\n";
		}

		return [
			'insert_string' => trim($inserts),
			'rows_exported' => count($rows)
		];
	}

	/**
	 * We need to balance keeping our INSERT query numbers small (for smaller
	 * file sizes and potentially faster imports) while also making sure a
	 * single query doesn't get too long, so here we'll break up a given array
	 * of rows into chunks that can likely be placed into a single query.
	 * MySQL's max_allowed_packet defaults to 4MB, but we'll shoot for under 3MB
	 * to be safe.
	 *
	 * @param	array	$rows	Rows of data pre-formatted for a VALUES string
	 * @return	array	Array of groups of rows
	 *	[
	 *		[ // One query's values
	 *			"(1, NULL, 'some value')",
	 *			"(2, NULL, 'another value')"
	 *		],
	 *		[ // Another query's values
	 *			"(3, NULL, 'some value')",
	 *			"(4, NULL, 'another value')"
	 *		],
	 *		...
	 *	]
	 */
	protected function makeRowChunks($rows)
	{
		$row_chunks = [];
		$byte_count = 0;
		$current_chunk = 0;

		foreach ($rows as $row)
		{
			// We'll assume that each character is roughly a byte
			$row_length = strlen($row) + 2;

			// We check for empty because even if the given row is too large
			// too fit in a query by itself, we have to export it anyway
			if (empty($row_chunks[$current_chunk]) OR
				$row_length + $byte_count < $this->query_size_limit)
			{
				$byte_count += $row_length;
			}
			// Reset the byte count for a new chunk and start a new chunk
			else
			{
				$current_chunk++;
				$byte_count = $row_length;
			}

			$row_chunks[$current_chunk][] = $row;
		}

		return $row_chunks;
	}

	/**
	 * Gets values for a table formatted for a VALUES string
	 *
	 * @param	string	$table_name		Table name
	 * @param	int		$offset			Query offset
	 * @param	int		$limit			Query limit
	 * @return	array	Array of groups of values to follow an INSERT INTO ... VALUES statement, e.g.
	 *	[
	 *		'(1, NULL, 'some value')',
	 *		'(2, NULL, 'another value')'
	 *	]
	 */
	protected function getValuesForTable($table_name, $offset, $limit)
	{
		$data = $this->query
			->offset($offset)
			->limit($limit)
			->get($table_name)
			->result_array();

		$values = [];
		foreach ($data as $row)
		{
			// Faster than array_map
			foreach ($row as $column_name => &$value)
			{
				$value = $this->formatValue(
					$value,
					$this->columnIsBinary($table_name, $column_name)
				);
			}

			$values[] = sprintf('(%s)', implode(', ', $row));
		}

		return $values;
	}

	/**
	 * Formats a given database value for use in a VALUES string
	 *
	 * @param	mixed	$value		Database column value
	 * @param	boolean	$is_binary	Whether or not the data is binary
	 * @return	mixed	Typically either a string or number, but formatted for
	 *                  a VALUES string
	 */
	protected function formatValue($value, $is_binary)
	{
		if ($is_binary)
		{
			$hex = '';
			foreach(str_split($value) as $char)
			{
				$hex .= str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);
			}

			return sprintf("x'%s'", $hex);
		}

		if (is_null($value))
		{
			return 'NULL';
		}
		elseif (is_numeric($value))
		{
			return $value;
		}
		else
		{
			return sprintf("'%s'", $this->query->escape_str($value));
		}
	}

	/**
	 * Gathers column types for a given table
	 *
	 * @param	string	$table_name	Table name
	 * @return	array	Associative array of tables to columns => type
	 */
	protected function getTypesForTable($table_name)
	{
		if ( ! isset($this->columns[$table_name]))
		{
			$this->columns[$table_name] = [];

			$query = $this->query
				->query(sprintf('DESCRIBE `%s`', $table_name));

			foreach ($query->result() as $row)
			{
				$this->columns[$table_name][$row->Field] = $row->Type;
			}
		}

		return $this->columns[$table_name];
	}

	/**
	 * Given a table and column name, determines if that column is of a binary type
	 *
	 * @param	string	$table_name		Table name
	 * @param	string	$column_name	Column name
	 * @return	boolean	Whether or not the column is of binary type
	 */
	protected function columnIsBinary($table_name, $column_name)
	{
		$types = $this->getTypesForTable($table_name);

		if ( ! isset($types[$column_name]))
		{
			throw new \Exception('Non-existant column requested: '. $column_name, 1);
		}

		$type = strtolower($types[$column_name]);

		return (
			strpos($type, 'binary') !== FALSE OR
			strpos($type, 'blob') !== FALSE
		);
	}
}

// EOF
