<?php

class Student extends FHCAPI_Controller
{

	private $_ci; // Code igniter instance
	private $_uid;
	private $language;

	const BERECHTIGUNG_KURZBZ = 'extension/internationalStudent';
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
				'studentAddMassnahme' => self::BERECHTIGUNG_KURZBZ .':rw',
				'studentDeleteNachweis' => self::BERECHTIGUNG_KURZBZ .':rw',
				'studentDeleteMassnahme' => self::BERECHTIGUNG_KURZBZ .':rw',
				'studentAddNachweis' => self::BERECHTIGUNG_KURZBZ .':rw',
				'getData' => self::BERECHTIGUNG_KURZBZ .':rw',
			)
		);

		$this->_ci =& get_instance();
		$this->loadPhrases(
			array(
				'lehre',
				'ui',
				'international',
				'global'
			)
		);

		$this->load->library('WidgetLib');
		$this->load->library('DmsLib');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahme_model', 'InternatmassnahmeModel');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahmezuordnung_model', 'InternatmassnahmezuordnungModel');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahmezuordnungstatus_model', 'InternatmassnahmezuordnungstatusModel');
		$this->_ci->load->model('crm/Student_model', 'StudentModel');
		$this->_ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
		$this->_ci->load->model('system/Sprache_model', 'SpracheModel');

		$this->load->helper('form');

		$this->_ci->load->config('extensions/FHC-Core-International/international');

		$this->setControllerId(); // sets the controller id
		$this->_setAuthUID();

		$this->language = getUserLanguage() === 'German' ? '0' : '1';

	}

	public function getData()
	{
		$this->_ci->SpracheModel->addSelect('index');
		$result = $this->_ci->SpracheModel->loadWhere(array('sprache' => getUserLanguage()));

		$language =  hasData($result) ? getData($result)[0]->index : 1;
		$result = $this->_ci->InternatmassnahmezuordnungModel->getDataStudent($this->_uid, $language);

		$this->terminateWithSuccess(hasData($result) ? getData($result) : []);
	}
	public function studentAddMassnahme()
	{
		$massnahmePost = $this->_ci->input->post('massnahme')['massnahme_id'];
		$studiensemesterPost = $this->_ci->input->post('studiensemester');
		$anmerkungPost = $this->_ci->input->post('anmerkung');

		if (isEmptyString((string)$massnahmePost) || isEmptyString($studiensemesterPost))
			$this->terminateWithError($this->_ci->p->t('ui', 'felderFehlen'), self::ERROR_TYPE_GENERAL);

		$this->_ci->StudentModel->addJoin('public.tbl_studiengang', 'studiengang_kz');
		$student = $this->_ci->StudentModel->loadWhere(array('student_uid' => $this->_uid));

		if (isError($student))
			$this->terminateWithError(getError($student), self::ERROR_TYPE_GENERAL);

		$student = getData($student)[0];

		$massnahme = $this->_ci->InternatmassnahmeModel->loadWhere(array('massnahme_id' => $massnahmePost, 'aktiv' => true));

		if (isError($massnahme))
			$this->terminateWithError(getError($massnahme), self::ERROR_TYPE_GENERAL);

		if (!hasData($massnahme))
			$this->terminateWithError($this->_ci->p->t('ui', 'fehlerBeimLesen'), self::ERROR_TYPE_GENERAL);

		$massnahme = getData($massnahme)[0];

		$studiensemester = $this->_ci->StudiensemesterModel->load(array('studiensemester_kurzbz' => $studiensemesterPost));

		if (isError($studiensemester))
			$this->terminateWithError(getError($studiensemester), self::ERROR_TYPE_GENERAL);

		if (!hasData($studiensemester))
			$this->terminateWithError($this->_ci->p->t('ui', 'fehlerBeimLesen'), self::ERROR_TYPE_GENERAL);

		$studiensemester = getData($studiensemester)[0];

		if ($massnahme->einmalig)
		{
			$already_exists = $this->_ci->InternatmassnahmezuordnungModel->checkIfExists($student->prestudent_id, $massnahme->massnahme_id);

			if (hasData($already_exists))
			{
				$this->terminateWithError($this->_ci->p->t('international', 'erroreinmalig'), self::ERROR_TYPE_GENERAL);
			}
		}

		$insert = $this->_ci->InternatmassnahmezuordnungModel->insert(
			array(
				'prestudent_id' => $student->prestudent_id,
				'massnahme_id' => $massnahme->massnahme_id,
				'studiensemester_kurzbz' => $studiensemester->studiensemester_kurzbz,
				'anmerkung' => $anmerkungPost,
				'insertamum' => date('Y-m-d H:i:s'),
				'insertvon' => $this->_uid
			)
		);

		if (isError($insert))
			$this->terminateWithError(getError($insert), self::ERROR_TYPE_GENERAL);

		$insertStatus = $this->_ci->InternatmassnahmezuordnungstatusModel->insert(
			array(
				'massnahme_zuordnung_id' => $insert->retval,
				'datum' => date ('Y-m-d'),
				'massnahme_status_kurzbz' => 'planned',
				'insertamum' => date('Y-m-d H:i:s'),
				'insertvon' => $this->_uid
			)
		);

		if (isError($insertStatus))
			$this->terminateWithError(getError($insertStatus), self::ERROR_TYPE_GENERAL);

		$this->terminateWithSuccess(array
									(
										'massnahme_zuordnung_id' => $insert->retval,
										'bezeichnung' => $massnahme->bezeichnung_mehrsprachig[$this->language],
										'studiensemester' => $studiensemester->studiensemester_kurzbz,
										'ects' => $massnahme->ects,
										'anmerkung' => $anmerkungPost,
										'massnahme_id' => $massnahme->massnahme_id
									)
		);
	}

	public function studentDeleteNachweis()
	{
		$massnahmenZuordnungPost = $this->_ci->input->post('massnahmenZuordnung');

		if (isEmptyString($massnahmenZuordnungPost))
			$this->terminateWithError($this->_ci->p->t('ui', 'felderFehlen'), self::ERROR_TYPE_GENERAL);

		$massnahmenZuordnung = $this->_checkMassnahmenZuordnung($massnahmenZuordnungPost);

		/*
		 * Solang die Bestätigung nicht akzeptiert wurde, kann sie gelöscht werden
		 */
		if ($massnahmenZuordnung->massnahme_status_kurzbz !== 'confirmed' && $massnahmenZuordnung->massnahme_status_kurzbz !== 'declined')
		{
			$updateZuordnung = $this->_ci->InternatmassnahmezuordnungModel->update(
				array('massnahme_zuordnung_id' => $massnahmenZuordnung->massnahme_zuordnung_id),
				array
				(
					'dms_id' => null,
					'updateamum' => date('Y-m-d H:i:s'),
					'updatevon' => $this->_uid
				)
			);

			if (isError($updateZuordnung))
				$this->terminateWithError(getError($updateZuordnung), self::ERROR_TYPE_GENERAL);

			$deleteFile = $this->_ci->dmslib->delete($massnahmenZuordnung->person_id, $massnahmenZuordnung->dms_id);

			if (isError($deleteFile))
				$this->terminateWithError(getError($deleteFile), self::ERROR_TYPE_GENERAL);

			$insertStatus = $this->_ci->InternatmassnahmezuordnungstatusModel->insert(
				array(
					'massnahme_zuordnung_id' => $massnahmenZuordnung->massnahme_zuordnung_id,
					'datum' => date ('Y-m-d'),
					'massnahme_status_kurzbz' => 'accepted',
					'insertamum' => date('Y-m-d H:i:s'),
					'insertvon' => $this->_uid
				)
			);

			if (isError($insertStatus))
				$this->terminateWithError(getError($insertStatus), self::ERROR_TYPE_GENERAL);

			$this->terminateWithSuccess($massnahmenZuordnung->massnahme_zuordnung_id);
		}
	}

	public function studentDeleteMassnahme()
	{
		$massnahmenZuordnungPost = $this->_ci->input->post('massnahmenZuordnung');

		if (isEmptyString($massnahmenZuordnungPost))
			$this->terminateWithError($this->_ci->p->t('ui', 'felderFehlen'), self::ERROR_TYPE_GENERAL);

		$massnahmenZuordnung = $this->_checkMassnahmenZuordnung($massnahmenZuordnungPost);

		/*
		 * Solang die Maßnahme nicht bestätigt wurde kann sie gelöscht werden
		 */
		if ($massnahmenZuordnung->massnahme_status_kurzbz !== 'confirmed' && $massnahmenZuordnung->massnahme_status_kurzbz !== 'declined')
		{
			$deleteStatus = $this->_ci->InternatmassnahmezuordnungstatusModel->delete(array('massnahme_zuordnung_id' => $massnahmenZuordnung->massnahme_zuordnung_id));

			if (isError($deleteStatus))
				$this->terminateWithError(getError($deleteStatus), self::ERROR_TYPE_GENERAL);

			$deleteZuordnung = $this->_ci->InternatmassnahmezuordnungModel->delete(array('massnahme_zuordnung_id' => $massnahmenZuordnung->massnahme_zuordnung_id));

			if (isError($deleteZuordnung))
				$this->terminateWithError(getError($deleteZuordnung), self::ERROR_TYPE_GENERAL);

			if (!is_null($massnahmenZuordnung->dms_id))
			{
				$deleteFile = $this->_ci->dmslib->delete($massnahmenZuordnung->person_id, $massnahmenZuordnung->dms_id);

				if (isError($deleteFile))
					$this->terminateWithError(getError($deleteFile), self::ERROR_TYPE_GENERAL);
			}

			$this->terminateWithSuccess(getData($deleteZuordnung));
		}
	}

	public function studentAddNachweis()
	{
		$massnahmenZuordnungPost = $this->_ci->input->post('massnahmenZuordnung');

		if (empty($_FILES['file']['name']) || isEmptyString($massnahmenZuordnungPost))
			$this->terminateWithError($this->_ci->p->t('ui', 'felderFehlen'), self::ERROR_TYPE_GENERAL);

		$massnahme = $this->_checkMassnahmenZuordnung($massnahmenZuordnungPost);

		if ($massnahme->massnahme_status_kurzbz !== 'accepted')
			$this->terminateWithError($this->_ci->p->t('ui', 'fehlerBeimSpeichern'), self::ERROR_TYPE_GENERAL);

		$dmsFile = $this->_uploadFile();

		if (isError($dmsFile))
			$this->terminateWithError(getError($dmsFile), self::ERROR_TYPE_GENERAL);

		$dmsFile = getData($dmsFile);

		$update = $this->_ci->InternatmassnahmezuordnungModel->update(
			array('massnahme_zuordnung_id' => $massnahme->massnahme_zuordnung_id),
			array
			(
				'dms_id' => $dmsFile['dms_id'],
				'updateamum' => date('Y-m-d H:i:s'),
				'updatevon' => $this->_uid
			)
		);

		if (isError($update))
			$this->terminateWithError(getError($update), self::ERROR_TYPE_GENERAL);

		$insertStatus = $this->_ci->InternatmassnahmezuordnungstatusModel->insert(
			array(
				'massnahme_zuordnung_id' => $massnahme->massnahme_zuordnung_id,
				'datum' => date ('Y-m-d'),
				'massnahme_status_kurzbz' => 'performed',
				'insertamum' => date('Y-m-d H:i:s'),
				'insertvon' => $this->_uid
			)
		);

		if (isError($insertStatus))
			$this->terminateWithError(getError($insertStatus), self::ERROR_TYPE_GENERAL);

		$this->terminateWithSuccess(array('dms_id' => $dmsFile['dms_id'], 'massnahme' => getData($update)['massnahme_zuordnung_id']));
	}

	private function _checkMassnahmenZuordnung($massnahmenZuordnungID)
	{
		$student = $this->_ci->StudentModel->loadWhere(array('student_uid' => $this->_uid));

		if (isError($student))
			$this->terminateWithError(getError($student), self::ERROR_TYPE_GENERAL);

		$student = getData($student)[0];

		$massnahmenZuordnung = $this->_ci->InternatmassnahmezuordnungModel->getMassnahmenWithZuordnung($student->prestudent_id, $massnahmenZuordnungID);

		if (isError($massnahmenZuordnung))
			$this->terminateWithError(getError($massnahmenZuordnung), self::ERROR_TYPE_GENERAL);

		if (!hasData($massnahmenZuordnung))
			$this->terminateWithError($this->_ci->p->t('ui', 'fehlerBeimLesen'), self::ERROR_TYPE_GENERAL);

		return getData($massnahmenZuordnung)[0];
	}

	private function _uploadFile()
	{
		$file = array(
			'kategorie_kurzbz' => 'international_nachweis',
			'version' => 0,
			'name' => $_FILES['file']['name'],
			'mimetype' => $_FILES['file']['type'],
			'insertamum' => (new DateTime())->format('Y-m-d H:i:s'),
			'insertvon' => $this->_uid
		);

		return $this->_ci->dmslib->upload($file, 'file', array('pdf'));
	}

	/**
	 * Retrieve the UID of the logged user and checks if it is valid
	 */
	private function _setAuthUID()
	{
		$this->_uid = getAuthUID();

		if (!$this->_uid) show_error('User authentification failed');
	}
}