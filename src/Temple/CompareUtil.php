<?php

namespace Temple;

class CompareUtil
{
	private $field;

	public function __construct($field)
	{
		$this->field = $field;
	}

	public function compareStringFields($row1, $row2)
	{
		return strcmp(Util::lavnn($this->field, $row1, ''), Util::lavnn($this->field, $row2, ''));
	}
}
