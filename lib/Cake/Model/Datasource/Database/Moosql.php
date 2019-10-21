<?php
/**
 * 
 * Socialloft
 * 
 * Moosocial team
 * 
 */

App::uses('Mysql', 'Model/Datasource/Database');

class Moosql extends Mysql {
	public $mooFieldSeparator = null;
	
	public function fetchResult() {
		if ($row = $this->_result->fetch(PDO::FETCH_NUM)) {
			$resultRow = array();
			foreach ($this->map as $col => $meta) {
				list($table, $column, $type) = $meta;
				$resultRow[$table][$column] = $row[$col];
				if ($type === 'boolean' && $row[$col] !== null) {
					$resultRow[$table][$column] = $this->boolean($resultRow[$table][$column]);
				}
			}		
			$array_key = @array_keys($resultRow);
			
			if (is_array($array_key) && count($array_key))
			{
				foreach ($array_key as $alias)
				{
					$Model = ClassRegistry::getObject($alias);
					if ($Model instanceof AppModel)
					{
						$mooFieldSeparator = $Model->getMooFields();
						
						if ($mooFieldSeparator && count($mooFieldSeparator))
						{										
							foreach ($mooFieldSeparator as $mooField)
							{
								$method = 'get'.ucfirst(strtolower($mooField));
								if ($Model instanceof  AppModel && method_exists($Model,$method))
								{
									$resultRow[$alias]['moo_'.$mooField] = $Model->{$method}($resultRow[$alias]);
								}
							}
						}
					}
				}
			}
					
			return $resultRow;
		}
		$this->_result->closeCursor();
		return false;
	}
	
	protected function _quoteFields($conditions) {
		$start = $end = null;
		$original = $conditions;
	
		if (!empty($this->startQuote)) {
			$start = preg_quote($this->startQuote);
		}
		if (!empty($this->endQuote)) {
			$end = preg_quote($this->endQuote);
		}
		// Remove quotes and requote all the Model.field names.
		$conditions = str_replace(array($start, $end), '', $conditions);
		$conditions = preg_replace_callback(
				'/(?:[\'\"][^\'\"\\\]*(?:\\\.[^\'\"\\\]*)*[\'\"])|([a-z0-9_][a-z0-9\\-_]*\\.[a-z0-9_][a-z0-9_\\-]*)/ui',
				array(&$this, '_quoteMatchedField'),
				$conditions
				);
		// Quote `table_name AS Alias`
		$conditions = preg_replace(
				'/(\s[a-z0-9\\-_.' . $start . $end . ']*' . $end . ')\s+AS\s+([a-z0-9\\-_]+)/ui',
				'\1 AS ' . $this->startQuote . '\2' . $this->endQuote,
				$conditions
				);
		if ($conditions !== null) {
			return $conditions;
		}
		return $original;
	}
	
	
	public function value($data, $column = null, $null = true) {
		if ($column && strpos($column,'enum') !== FALSE && is_string($data))
		{
			return $this->_connection->quote($data);
		}
		
		return parent::value($data,$column,$null);
	}
}