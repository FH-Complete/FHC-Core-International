<?php

class Internatmassnahme_model extends DB_Model
{

	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_internat_massnahme';
		$this->pk = array('massnahme_id');
		$this->hasSequence = true;
	}
}
