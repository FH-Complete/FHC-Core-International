<?php

class Internatmassnahmestatus_model extends DB_Model
{

	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_internat_massnahme_status';
		$this->hasSequence = false;
	}
}
