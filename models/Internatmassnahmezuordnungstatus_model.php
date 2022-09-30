<?php

class Internatmassnahmezuordnungstatus_model extends DB_Model
{

	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_internat_massnahme_zuordnung_status';
		$this->pk = array('massnahme_zuordnung_status_id');
		$this->hasSequence = true;
	}
}
