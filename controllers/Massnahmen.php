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
				'addMassnahme' => self::BERECHTIGUNG_KURZBZ .':rw',
				'updateMassnahme' => self::BERECHTIGUNG_KURZBZ .':rw',
				'deleteMassnahme' => self::BERECHTIGUNG_KURZBZ .':rw',
				'showMassnahme' => self::BERECHTIGUNG_KURZBZ .':rw'
			)
		);

		$this->_ci =& get_instance();
		$this->loadPhrases(
			array(
				'global',
				'ui',
				'international',
				'lehre'
			)
		);

		$this->load->library('WidgetLib');
		$this->load->library('DmsLib');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahme_model', 'InternatmassnahmeModel');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahmezuordnung_model', 'InternatmassnahmezuordnungModel');

		$this->setControllerId(); // sets the controller id
		$this->_setAuthUID();
	}

	public function index()
	{
		$this->_ci->load->view('extensions/FHC-Core-International/massnahmen/massnahmen.php');
	}

	public function addMassnahme()
	{
		$bezeichnung = $this->_ci->input->post('bezeichnung');
		$bezeichnungeng = $this->_ci->input->post('bezeichnungeng');
		$ects = $this->_ci->input->post('ects');
		$beschreibung = $this->_ci->input->post('beschreibung');
		$beschreibungeng = $this->_ci->input->post('beschreibungeng');
		$aktiv = $this->_ci->input->post('aktiv');

		if (isEmptyString($bezeichnung) || isEmptyString($bezeichnungeng) || isEmptyString($ects)
			|| isEmptyString($beschreibung) || isEmptyString($beschreibungeng))
		{
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));
		}

		$bezeichnungmehrsprachig = "{". $bezeichnung. ", ". $bezeichnungeng . "}";
		$beschreibungmehrsprachig = "{". $beschreibung. ", ". $beschreibungeng . "}";

		$insert = $this->_ci->InternatmassnahmeModel->insert(array
			(
				'bezeichnung_mehrsprachig' => $bezeichnungmehrsprachig,
				'beschreibung_mehrsprachig' => $beschreibungmehrsprachig,
				'ects' => $ects,
				'aktiv' => $aktiv === 'true',
				'insertamum' => date('Y-m-d H:i:s'),
				'insertvon' => $this->_uid
			)
		);

		if (isError($insert))
			$this->terminateWithJsonError(getError($insert));


		$bezeichnung = getUserLanguage() === 'German' ? $bezeichnung : $bezeichnungeng;
		$beschreibung = getUserLanguage() === 'German' ? $beschreibung : $beschreibungeng;
		$this->outputJsonSuccess(array
			(
				'massnahme_id' => $insert->retval,
				'bezeichnung' => $bezeichnung,
				'beschreibung' => $beschreibung,
				'aktiv' => $aktiv,
				'ects' => $ects
			)
		);

	}

	public function updateMassnahme()
	{
		$massnahmeid = $this->_ci->input->post('hiddenmassnahmenid');
		$bezeichnung = $this->_ci->input->post('bezeichnung');
		$bezeichnungeng = $this->_ci->input->post('bezeichnungeng');
		$ects = $this->_ci->input->post('ects');
		$beschreibung = $this->_ci->input->post('beschreibung');
		$beschreibungeng = $this->_ci->input->post('beschreibungeng');
		$aktiv = $this->_ci->input->post('aktiv');

		if (isEmptyString($bezeichnung) || isEmptyString($bezeichnungeng) || isEmptyString($ects)
			|| isEmptyString($beschreibung) || isEmptyString($beschreibungeng))
		{
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));
		}

		$bezeichnungmehrsprachig = "{". $bezeichnung. ", ". $bezeichnungeng . "}";
		$beschreibungmehrsprachig = "{". $beschreibung. ", ". $beschreibungeng . "}";

		$insert = $this->_ci->InternatmassnahmeModel->update(
			array('massnahme_id' => $massnahmeid),
			array
			(
				'bezeichnung_mehrsprachig' => $bezeichnungmehrsprachig,
				'beschreibung_mehrsprachig' => $beschreibungmehrsprachig,
				'ects' => $ects,
				'aktiv' => $aktiv === 'true',
				'updateamum' => date('Y-m-d H:i:s'),
				'updatevon' => $this->_uid
			)
		);

		if (isError($insert))
			$this->terminateWithJsonError(getError($insert));

		$this->outputJsonSuccess('Success');
	}

	public function deleteMassnahme()
	{
		$massnahmenID = $this->_ci->input->post('massnahmeID');

		if (isEmptyString($massnahmenID))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));

		$massnahmenZuordnungen = $this->_ci->InternatmassnahmezuordnungModel->load(array('massnahme_id' => $massnahmenID));

		if (isError($massnahmenZuordnungen))
			$this->terminateWithJsonError(getError($massnahmenZuordnungen));

		if (hasData($massnahmenZuordnungen))
			$this->terminateWithJsonError('Die Massnahme wurde bereits zugewiesen und kann nicht gelöscht werden');

		$delete = $this->_ci->InternatmassnahmeModel->delete(array('massnahme_id' => $massnahmenID));

		if (isError($delete))
			$this->terminateWithJsonError(getError($delete));

		$this->outputJsonSuccess('Success');
	}

	public function showMassnahme()
	{
		$this->_setNavigationMenuShowDetails();

		$massnahme_id = $this->_ci->input->get('massnahme_id');

		if (!is_numeric($massnahme_id))
			$this->terminateWithJsonError('massnahme id is not numeric!');

		$massnahmeexists = $this->_ci->InternatmassnahmeModel->load(array($massnahme_id));

		if (isError($massnahmeexists))
			$this->terminateWithJsonError(getError($massnahmeexists));

		if (!hasData($massnahmeexists))
			$this->terminateWithJsonError('Massnahme does not exist!');

		$massnahmeexists = getData($massnahmeexists)[0];

		$this->_ci->load->view('extensions/FHC-Core-International/massnahmen/massnahmenDetails.php', array('massnahme' => $massnahmeexists));
	}

	private function _setNavigationMenuShowDetails()
	{
		$this->load->library('NavigationLib', array('navigation_page' => 'extensions/FHC-Core-International/Massnahmen/showMassnahme'));

		$link = site_url('extensions/FHC-Core-International/Massnahmen');

		$this->_ci->navigationlib->setSessionMenu(
			array(
				'back' => $this->_ci->navigationlib->oneLevel(
					'Zurück',		// description
					$link,			// link
					array(),		// children
					'angle-left',	// icon
					true,			// expand
					null, 			// subscriptDescription
					null, 			// subscriptLinkClass
					null, 			// subscriptLinkValue
					'', 			// target
					1 				// sort
				)
			)
		);
	}

	private function _setAuthUID()
	{
		$this->_uid = getAuthUID();
		if (!$this->_uid) show_error('User authentification failed');
	}
}