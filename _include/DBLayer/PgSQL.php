<?php
/**
 * A database layer class that relies on the PostgreSQL PHP extension.
 *
 * @copyright (C) 2009-2013 Roman Parpalak, based on code (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package S2
 */


class DBLayer_PgSQL extends DBLayer_Abstract
{
	var $link_id;
	var $query_result;
	var $last_query_text = array();
	var $in_transaction = 0;

	var $datatype_transformations = array(
		'/^(TINY|SMALL)INT( )?(\\([0-9]+\\))?( )?(UNSIGNED)?$/i'			=>	'SMALLINT',
		'/^(MEDIUM)?INT( )?(\\([0-9]+\\))?( )?(UNSIGNED)?$/i'				=>	'INTEGER',
		'/^BIGINT( )?(\\([0-9]+\\))?( )?(UNSIGNED)?$/i'						=>	'BIGINT',
		'/^(TINY|MEDIUM|LONG)?TEXT$/i'										=>	'TEXT',
		'/^DOUBLE( )?(\\([0-9,]+\\))?( )?(UNSIGNED)?$/i'					=>	'DOUBLE PRECISION',
		'/^FLOAT( )?(\\([0-9]+\\))?( )?(UNSIGNED)?$/i'						=>	'REAL'
	);


	public function __construct($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect)
	{
		// Make sure we have built in support for PostgreSQL
		if (!function_exists('pg_connect'))
			throw new Exception('This PHP environment doesn\'t have PostgreSQL support built in. PostgreSQL support is required if you want to use a PostgreSQL database to run this site. Consult the PHP documentation for further assistance.');

		parent::__construct($db_prefix);

		if ($db_host)
		{
			if (strpos($db_host, ':') !== false)
			{
				list($db_host, $dbport) = explode(':', $db_host);
				$connect_str[] = 'host='.$db_host.' port='.$dbport;
			}
			else
				$connect_str[] = 'host='.$db_host;
		}

		if ($db_name)
			$connect_str[] = 'dbname='.$db_name;

		if ($db_username)
			$connect_str[] = 'user='.$db_username;

		if ($db_password)
			$connect_str[] = 'password='.$db_password;

		if ($p_connect)
			$this->link_id = @pg_pconnect(implode(' ', $connect_str));
		else
			$this->link_id = @pg_connect(implode(' ', $connect_str));

		if (!$this->link_id)
			throw new DBLayer_Exception('Unable to connect to PostgreSQL server');

		// Setup the client-server character set (UTF-8)
		if (!defined('S2_NO_SET_NAMES'))
			$this->set_names('utf8');

		return $this->link_id;
	}


	function start_transaction()
	{
		++$this->in_transaction;

		return (@pg_query($this->link_id, 'BEGIN')) ? true : false;
	}


	function end_transaction()
	{
		--$this->in_transaction;

		if (@pg_query($this->link_id, 'COMMIT'))
			return true;
		else
		{
			@pg_query($this->link_id, 'ROLLBACK');
			return false;
		}
	}


	function query($sql, $unbuffered = false) // $unbuffered is ignored since there is no pgsql_unbuffered_query()
	{
		if (strrpos($sql, 'LIMIT') !== false)
			$sql = preg_replace('#LIMIT ([0-9]+),([ 0-9]+)#', 'LIMIT \\2 OFFSET \\1', $sql);

		if (defined('S2_SHOW_QUERIES'))
			$q_start = microtime(true);

		@pg_send_query($this->link_id, $sql);
		$this->query_result = @pg_get_result($this->link_id);

		if (isset($q_start))
			$this->saved_queries[] = array($sql, microtime(true) - $q_start);

		if (pg_result_status($this->query_result) == PGSQL_FATAL_ERROR)
		{
			if ($this->in_transaction)
				@pg_query($this->link_id, 'ROLLBACK');

			--$this->in_transaction;

			throw new DBLayer_Exception(@pg_result_error($this->query_result), false, $sql);
		}

		++$this->num_queries;

		$this->last_query_text[$this->query_result] = $sql;

		return $this->query_result;
	}


	function query_build($query, $return_query_string = false, $unbuffered = false)
	{
		$sql = '';

		if (isset($query['SELECT']))
		{
			$sql = 'SELECT '.$query['SELECT'].' FROM '.(isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).$query['FROM'];

			if (isset($query['JOINS']))
			{
				foreach ($query['JOINS'] as $cur_join)
					$sql .= ' '.key($cur_join).' '.(isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).current($cur_join).' ON '.$cur_join['ON'];
			}

			if (!empty($query['WHERE']))
				$sql .= ' WHERE '.$query['WHERE'];
			if (!empty($query['GROUP BY']))
				$sql .= ' GROUP BY '.$query['GROUP BY'];
			if (!empty($query['HAVING']))
				$sql .= ' HAVING '.$query['HAVING'];
			if (!empty($query['ORDER BY']))
				$sql .= ' ORDER BY '.$query['ORDER BY'];
			if (!empty($query['LIMIT']))
				$sql .= ' LIMIT '.$query['LIMIT'];
		}
		else if (isset($query['INSERT']))
		{
			$sql = 'INSERT INTO '.(isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).$query['INTO'];

			if (!empty($query['INSERT']))
				$sql .= ' ('.$query['INSERT'].')';

			if (is_array($query['VALUES']))
			{
				$new_query = $query;
				if ($return_query_string)
				{
					$query_set = array();
					foreach ($query['VALUES'] as $cur_values)
					{
						$new_query['VALUES'] = $cur_values;
						$query_set[] = $this->query_build($new_query, true, $unbuffered);
					}

					$sql = implode('; ', $query_set);
				}
				else
				{
					$result_set = null;
					foreach ($query['VALUES'] as $cur_values)
					{
						$new_query['VALUES'] = $cur_values;
						$result_set = $this->query_build($new_query, false, $unbuffered);
					}

					return $result_set;
				}
			}
			else
				$sql .= ' VALUES('.$query['VALUES'].')';
		}
		else if (isset($query['UPDATE']))
		{
			$query['UPDATE'] = (isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).$query['UPDATE'];

			$sql = 'UPDATE '.$query['UPDATE'].' SET '.$query['SET'];

			if (!empty($query['WHERE']))
				$sql .= ' WHERE '.$query['WHERE'];
		}
		else if (isset($query['DELETE']))
		{
			$sql = 'DELETE FROM '.(isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).$query['DELETE'];

			if (!empty($query['WHERE']))
				$sql .= ' WHERE '.$query['WHERE'];
		}
		else if (isset($query['REPLACE']))
		{
			$sql = 'INSERT INTO '.(isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).$query['INTO'];

			if (!empty($query['REPLACE']))
				$sql .= ' ('.$query['REPLACE'].')';

			$sql .= ' SELECT '.$query['VALUES'].' WHERE NOT EXISTS (SELECT 1 FROM '.(isset($query['PARAMS']['NO_PREFIX']) ? '' : $this->prefix).$query['INTO'].' WHERE '.$query['UNIQUE'].')';
		}

		return ($return_query_string) ? $sql : $this->query($sql, $unbuffered);
	}


	function result($query_id = 0, $row = 0, $col = 0)
	{
		return ($query_id) ? @pg_fetch_result($query_id, $row, $col) : false;
	}


	function fetch_assoc($query_id = 0)
	{
		return ($query_id) ? @pg_fetch_assoc($query_id) : false;
	}


	function fetch_row($query_id = 0)
	{
		return ($query_id) ? @pg_fetch_row($query_id) : false;
	}


	function num_rows($query_id = 0)
	{
		return ($query_id) ? @pg_num_rows($query_id) : false;
	}


	function affected_rows()
	{
		return ($this->query_result) ? @pg_affected_rows($this->query_result) : false;
	}


	function insert_id()
	{
		$query_id = $this->query_result;

		if ($query_id && $this->last_query_text[$query_id] != '' && preg_match('/^INSERT INTO ([a-z0-9\_\-]+)/is', $this->last_query_text[$query_id], $table_name))
		{
			$temp_q_id = @pg_query($this->link_id, 'SELECT sequence_name FROM information_schema.sequences WHERE sequence_name LIKE \'%'.$table_name[1].'%\'');
			if (!$temp_q_id)
				return false;

			$temp_q_id = @pg_query($this->link_id, 'SELECT currval(\''.(@pg_fetch_result($temp_q_id, 0)).'\')');
			return ($temp_q_id) ? intval(@pg_fetch_result($temp_q_id, 0)) : false;
		}

		return false;
	}


	function free_result($query_id = false)
	{
		if (!$query_id)
			$query_id = $this->query_result;

		return ($query_id) ? @pg_free_result($query_id) : false;
	}


	function escape($str)
	{
		return is_array($str) ? '' : pg_escape_string($str);
	}


	function close()
	{
		if ($this->link_id)
		{
			if ($this->in_transaction)
			{
				if (defined('S2_SHOW_QUERIES'))
					$this->saved_queries[] = array('COMMIT', 0);

				@pg_query($this->link_id, 'COMMIT');
			}

			if ($this->query_result)
				@pg_free_result($this->query_result);

			return @pg_close($this->link_id);
		}
		else
			return false;
	}


	function get_version()
	{
		$result = $this->query('SELECT VERSION()');

		return array(
			'name'		=> 'PostgreSQL',
			'version'	=> preg_replace('/^[^0-9]+([^\s,-]+).*$/', '\\1', $this->result($result))
		);
	}


	function table_exists($table_name, $no_prefix = false)
	{
		$result = $this->query('SELECT 1 FROM pg_class WHERE relname = \''.($no_prefix ? '' : $this->prefix).$this->escape($table_name).'\'');
		return $this->num_rows($result) > 0;
	}


	function field_exists($table_name, $field_name, $no_prefix = false)
	{
		$result = $this->query('SELECT 1 FROM pg_class c INNER JOIN pg_attribute a ON a.attrelid = c.oid WHERE c.relname = \''.($no_prefix ? '' : $this->prefix).$this->escape($table_name).'\' AND a.attname = \''.$this->escape($field_name).'\'');
		return $this->num_rows($result) > 0;
	}


	function index_exists($table_name, $index_name, $no_prefix = false)
	{
		$result = $this->query('SELECT 1 FROM pg_index i INNER JOIN pg_class c1 ON c1.oid = i.indrelid INNER JOIN pg_class c2 ON c2.oid = i.indexrelid WHERE c1.relname = \''.($no_prefix ? '' : $this->prefix).$this->escape($table_name).'\' AND c2.relname = \''.($no_prefix ? '' : $this->prefix).$this->escape($table_name).'_'.$this->escape($index_name).'\'');
		return $this->num_rows($result) > 0;
	}


	function create_table($table_name, $schema, $no_prefix = false)
	{
		if ($this->table_exists($table_name, $no_prefix))
			return;

		$query = 'CREATE TABLE '.($no_prefix ? '' : $this->prefix).$table_name." (\n";

		// Go through every schema element and add it to the query
		foreach ($schema['FIELDS'] as $field_name => $field_data)
		{
			$field_data['datatype'] = preg_replace(array_keys($this->datatype_transformations), array_values($this->datatype_transformations), $field_data['datatype']);

			$query .= $field_name.' '.$field_data['datatype'];

			// The SERIAL datatype is a special case where we don't need to say not null
			if (!$field_data['allow_null'] && $field_data['datatype'] != 'SERIAL')
				$query .= ' NOT NULL';

			if (isset($field_data['default']))
				$query .= ' DEFAULT '.$field_data['default'];

			$query .= ",\n";
		}

		// If we have a primary key, add it
		if (isset($schema['PRIMARY KEY']))
			$query .= 'PRIMARY KEY ('.implode(',', $schema['PRIMARY KEY']).'),'."\n";

		// Add unique keys
		if (isset($schema['UNIQUE KEYS']))
		{
			foreach ($schema['UNIQUE KEYS'] as $key_name => $key_fields)
				$query .= 'UNIQUE ('.implode(',', $key_fields).'),'."\n";
		}

		// We remove the last two characters (a newline and a comma) and add on the ending
		$query = substr($query, 0, strlen($query) - 2)."\n".')';

		$this->query($query);

		// Add indexes
		if (isset($schema['INDEXES']))
		{
			foreach ($schema['INDEXES'] as $index_name => $index_fields)
				$this->add_index($table_name, $index_name, $index_fields, false, $no_prefix);
		}
	}


	function drop_table($table_name, $no_prefix = false)
	{
		if (!$this->table_exists($table_name, $no_prefix))
			return;

		$this->query('DROP TABLE '.($no_prefix ? '' : $this->prefix).$table_name);
	}


	function add_field($table_name, $field_name, $field_type, $allow_null, $default_value = null, $after_field = null, $no_prefix = false)
	{
		if ($this->field_exists($table_name, $field_name, $no_prefix))
			return;

		$field_type = preg_replace(array_keys($this->datatype_transformations), array_values($this->datatype_transformations), $field_type);

		$this->query('ALTER TABLE '.($no_prefix ? '' : $this->prefix).$table_name.' ADD '.$field_name.' '.$field_type);

		if ($default_value !== null)
		{
			if (!is_int($default_value) && !is_float($default_value))
				$default_value = '\''.$this->escape($default_value).'\'';

			$this->query('ALTER TABLE '.($no_prefix ? '' : $this->prefix).$table_name.' ALTER '.$field_name.' SET DEFAULT '.$default_value);
			$this->query('UPDATE '.($no_prefix ? '' : $this->prefix).$table_name.' SET '.$field_name.'='.$default_value);
		}

		if (!$allow_null)
			$this->query('ALTER TABLE '.($no_prefix ? '' : $this->prefix).$table_name.' ALTER '.$field_name.' SET NOT NULL');
	}


	function alter_field($table_name, $field_name, $field_type, $allow_null, $default_value = null, $after_field = null, $no_prefix = false)
	{
		if (!$this->field_exists($table_name, $field_name, $no_prefix))
			return;

		$field_type = preg_replace(array_keys($this->datatype_transformations), array_values($this->datatype_transformations), $field_type);

		$this->add_field($table_name, 'tmp_'.$field_name, $field_type, $allow_null, $default_value, $after_field, $no_prefix);
		$this->query('UPDATE '.($no_prefix ? '' : $this->prefix).$table_name.' SET tmp_'.$field_name.' = '.$field_name);
		$this->drop_field($table_name, $field_name, $no_prefix);
		$this->query('ALTER TABLE '.($no_prefix ? '' : $this->prefix).$table_name.' RENAME COLUMN tmp_'.$field_name.' TO '.$field_name);

		// Set the default value
		if ($default_value === null)
			$default_value = 'NULL';
		else if (!is_int($default_value) && !is_float($default_value))
			$default_value = '\''.$this->escape($default_value).'\'';

		$this->query('ALTER TABLE '.($no_prefix ? '' : $this->prefix).$table_name.' ALTER '.$field_name.' SET DEFAULT '.$default_value);

		if (!$allow_null)
			$this->query('ALTER TABLE '.($no_prefix ? '' : $this->prefix).$table_name.' ALTER '.$field_name.' SET NOT NULL');
	}


	function drop_field($table_name, $field_name, $no_prefix = false)
	{
		if (!$this->field_exists($table_name, $field_name, $no_prefix))
			return;

		$this->query('ALTER TABLE '.($no_prefix ? '' : $this->prefix).$table_name.' DROP '.$field_name);
	}


	function add_index($table_name, $index_name, $index_fields, $unique = false, $no_prefix = false)
	{
		if ($this->index_exists($table_name, $index_name, $no_prefix))
			return;

		$this->query('CREATE '.($unique ? 'UNIQUE ' : '').'INDEX '.($no_prefix ? '' : $this->prefix).$table_name.'_'.$index_name.' ON '.($no_prefix ? '' : $this->prefix).$table_name.'('.implode(',', $index_fields).')');
	}


	function drop_index($table_name, $index_name, $no_prefix = false)
	{
		if (!$this->index_exists($table_name, $index_name, $no_prefix))
			return;

		$this->query('DROP INDEX '.($no_prefix ? '' : $this->prefix).$table_name.'_'.$index_name);
	}
}
