<?php

namespace Temple;

class ArrayUtil
{
	public static function req_int($name, $default = 0) {
		$value = Util::lavnn($name, $_REQUEST, $default);

		return (!is_int($value)) ? $value : $default;
	}

	public static function req_str($name, $default = '') {
		return trim(Util::lavnn($name, $_REQUEST, $default));
	}

	/**
	 * Cuts two columns from two-dimensional array, using one of them as a key
	 *
	 * @param $arr
	 * @param string $keyField
	 * @param string $valueField
	 * @param string $select
	 *
	 * @return array
	 */
	public static function arr2arr($arr, $keyField = 'id', $valueField = 'name', $select = '')
	{
		$output = array();
		foreach ($arr as $row) {
			$key = Util::lavnn($keyField, $row, '');
			$value = Util::lavnn($valueField, $row, '');
			if ($key != '' && $value != '') {
				$output[$key] = array("key" => $key, "value" => $value);
				if ($select != '' && $key == $select) {
					$output[$key]["selected"] = 'selected';
				}
			}
		}

		return $output;
	}

	/**
	 * Cuts two columns two-dimensional array into a dictionary (hashtable)
	 *
	 * @param $arr
	 * @param $keyField
	 * @param $valueField
	 * @param int $allowEmptyValue
	 *
	 * @return array
	 */
	public static function arr2dict($arr, $keyField, $valueField, $allowEmptyValue = 0)
	{
		$output = array();
		foreach ($arr as $row) {
			$key = Util::lavnn($keyField, $row, '');
			$value = Util::lavnn($valueField, $row, '');
			if ($key != '' && (($value != '') || $allowEmptyValue > 0)) {
				$output[$key] = $value;
			}
		}

		return $output;
	}

	public static function set2dict($set)
	{
		$output = array();
		foreach($set as $element) {
			$output[$element] = $element;
		}

		return $output;
	}

	/**
	 * Transforms dictionary into associative key-value array
	 *
	 * @param $dict
	 * @param string $select
	 *
	 * @return array
	 */
	public static function dict2arr(
		$dict,
		$select = ''
	) {
		$output = array();
		foreach ($dict as $key => $value) {
			$output[$key] = array("key" => $key, "value" => $value);
			if ($select != '' && $key == $select) {
				$output[$key]["selected"] = 'selected';
			}
		}

		return $output;
	}

	public static function set2arr(
		$set,
		$select = ''
	) {
		return self::dict2arr(self::set2dict($set), $select);
	}

	public static function cut_column($column, $arr)
	{
		$values = array();
		foreach ($arr as $row) {
			$value = Util::lavnn($column, $row, '');
			if ($value != '') {
				$values[] = $value;
			}
		}

		return $values;
	}

	public static function add_column($arr, $columnName, $columnValue)
	{
		$values = array();
		foreach ($arr as $row) {
			$row[$columnName] = $columnValue;
			$values[] = $row;
		}

		return $values;
	}

	/**
	 * Implodes values of one column in an array
	 *
	 * @param $glue
	 * @param $column
	 * @param $arr
	 *
	 * @return string
	 */
	public static function implode_column(
		$glue,
		$column,
		$arr
	) {
		return implode($glue, self::cut_column($column, $arr));
	}

	/**
	 * Implodes values of one column in an array, using quotes
	 *
	 * @param $glue
	 * @param $column
	 * @param $arr
	 * @param string $quote
	 *
	 * @return string
	 */
	public static function implode_column_quoted(
		$glue,
		$column,
		$arr,
		$quote = "'"
	) {
		return $quote . implode($quote . $glue . $quote, self::cut_column($column, $arr)) . $quote;
	}

	/**
	 * Finds a sum of values of numeric column of two-dimensional array
	 *
	 * @param $arr
	 * @param $column
	 *
	 * @return int|string
	 */
	public static function sum_column(
		$arr,
		$column
	) {
		$output = 0;
		foreach ($arr as $row) {
			$value = Util::lavnn($column, $row, 0);
			if (is_numeric($value)) {
				$output += $value;
			}
		}

		return $output;
	}

	/**
	 * Slices two-dimensional array into associative array by values in a key column
	 *
	 * @param $arr
	 * @param $column
	 *
	 * @return array
	 */
	public static function slice_array(
		$arr,
		$column
	) {
		$output = array();
		foreach ($arr as $row) {
			$output[$row[$column]][] = $row;
		}

		return $output;
	}

	/**
	 * Reduces array by eliminating all rows where one column's values not equal to search
	 *
	 * @param $arr
	 * @param $column
	 * @param $value
	 *
	 * @return array
	 */
	public static function filter_array(
		$arr,
		$column,
		$value
	) {
		$output = array();
		foreach ($arr as $row) {
			if ($row[$column] == $value) {
				$output[] = $row;
			}
		}

		return $output;
	}

	/**
	 * @param $arr
	 * @param $keyField
	 * @param $valueField
	 * @param $selectValue
	 *
	 * @return array
	 */
	public static function genOptions(
		$arr,
		$keyField,
		$valueField,
		$selectValue
	) {
		$output = array();
		foreach ($arr as $item) {
			$option = array(
				'key' => $item[$keyField],
				'value' => $item[$valueField]
			);
			if ($option['key'] == $selectValue) {
				$option['selected'] = 'selected';
			}
			if ($option['key'] <> '' && $option['value'] <> '') {
				$output[] = $option;
			}
		}

		return $output;
	}

	/**
	 * @param $arr
	 * @param $columns
	 *
	 * @return array
	 */
	public static function splice_columns(
		$arr,
		$columns
	) {
		$output = array();
		foreach($arr as $row) {
			$newRow = array();
			foreach($row as $field => $value) {
				if (in_array($field, $columns)) {
					$newRow[$field] = $value;
				}
			}
			$output[] = $newRow;
		}

		return $output;
	}

    /**
     * @param array $baseArray
     * @param array $elementKeys
     *
     * @return bool
     */
	public static function hasElements(array $baseArray, array $elementKeys) {
		foreach($elementKeys as $key) {
			if (isset($baseArray[$key]) && is_array($baseArray[$key]) && count($baseArray[$key]) > 0) {
				return true;
			}
		}
		return false;
	}
}
