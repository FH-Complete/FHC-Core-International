<?php

class Massnahmen extends Auth_Controller
{

	private $_ci; // Code igniter instance
	private $_uid;

	const BERECHTIGUNG_KURZBZ = 'extension/internationalMassnahme';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
				'index' => self::BERECHTIGUNG_KURZBZ .':rw',
			)
		);

		$this->_ci =& get_instance();
		$this->setControllerId(); // sets the controller id
	}

	public function index()
	{
		$this->_ci->load->view('extensions/FHC-Core-International/massnahmen/massnahmen.php');
	}
}