<?php

class Student extends Auth_Controller
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
				'index' => self::BERECHTIGUNG_KURZBZ .':rw',
				'studentAddMassnahme' => self::BERECHTIGUNG_KURZBZ .':rw',
				'studentDeleteNachweis' => self::BERECHTIGUNG_KURZBZ .':rw',
				'studentDeleteMassnahme' => self::BERECHTIGUNG_KURZBZ .':rw',
				'studentAddNachweis' => self::BERECHTIGUNG_KURZBZ .':rw',
				'studentDownloadNachweis' => self::BERECHTIGUNG_KURZBZ .':rw',
				'getMassnahmen' => self::BERECHTIGUNG_KURZBZ .':rw'
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

		$this->load->helper('form');

		$this->_ci->load->config('extensions/FHC-Core-International/international');

		$this->setControllerId(); // sets the controller id
		$this->_setAuthUID();

		$this->language = getUserLanguage() === 'German' ? '0' : '1';

	}

	public function index()
	{
		$this->_ci->StudentModel->addJoin('public.tbl_studiengang', 'studiengang_kz');
		$student = $this->_ci->StudentModel->loadWhere(array('student_uid' => $this->_uid));

		if (isError($student))
			$this->terminateWithJsonError(getError($student));

		if (!hasData($student))
			show_error($this->_ci->p->t('international', 'nurBachelor'));

		$student = getData($student)[0];
		if ($student->typ !== 'b' || in_array($student->studiengang_kz, $this->_ci->config->item('stg_kz_blacklist')))
			show_error($this->_ci->p->t('international', 'nurBachelor'));

		$this->_ci->InternatmassnahmeModel->addOrder('ects');
		$this->_ci->InternatmassnahmeModel->addSelect('massnahme_id,
														ects,
														array_to_json(bezeichnung_mehrsprachig::varchar[])->>'.$this->language.' as bezeichnung,
														array_to_json(beschreibung_mehrsprachig::varchar[])->>'.$this->language.' as beschreibung');
		$massnahmen = $this->_ci->InternatmassnahmeModel->loadWhere(array('aktiv' => true));

		if (isError($massnahmen))
			$this->terminateWithJsonError(getError($massnahmen));

		$massnahmen = getData($massnahmen);

		$this->_ci->StudentModel->addLimit(1);
		$this->_ci->StudentModel->addOrder('ausbildungssemester', 'DESC');
		$this->_ci->StudentModel->addSelect('ausbildungssemester');
		$this->_ci->StudentModel->addJoin('public.tbl_prestudentstatus', 'prestudent_id');
		$ausbildungssemester = $this->_ci->StudentModel->loadWhere(array(
			'student_uid' => $this->_uid,
			'status_kurzbz' => 'Student'
		));

		if (isError($ausbildungssemester))
			$this->terminateWithJsonError(getError($ausbildungssemester));

		$ausbildungssemester = getData($ausbildungssemester)[0]->ausbildungssemester;

		$this->_ci->StudentModel->addSelect('max_semester');
		$this->_ci->StudentModel->addJoin('public.tbl_studiengang', 'studiengang_kz');
		$maxsemester = $this->_ci->StudentModel->load(array('student_uid' => $this->_uid));

		if (isError($maxsemester))
			$this->terminateWithJsonError(getError($maxsemester));

		$maxsemester = getData($maxsemester)[0]->max_semester;

		$diff = $maxsemester - $ausbildungssemester;

		$studiensemester = [];
		if ($diff !== 0)
		{
			$aktSemester = $this->_ci->StudiensemesterModel->getAktOrNextSemester();
			$this->_ci->StudiensemesterModel->addLimit($diff + 1);
			$this->_ci->StudiensemesterModel->addOrder('start');
			$studiensemester = $this->_ci->StudiensemesterModel->loadWhere(array('start >=' => getData($aktSemester)[0]->start));
			if (isError($studiensemester))
				$this->terminateWithJsonError(getError($studiensemester));

			$studiensemester = getData($studiensemester);
		}

		$this->_ci->load->view('extensions/FHC-Core-International/cis/student.php',
			array('massnahmen' => $massnahmen,
				'studiensemester' => $studiensemester)
		);
	}

	public function studentAddMassnahme()
	{
		$massnahmePost = $this->_ci->input->post('massnahme');
		$studiensemesterPost = $this->_ci->input->post('studiensemester');
		$anmerkungPost = $this->_ci->input->post('anmerkung');

		if (isEmptyString($massnahmePost) || isEmptyString($studiensemesterPost))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));

		$this->_ci->StudentModel->addJoin('public.tbl_studiengang', 'studiengang_kz');
		$student = $this->_ci->StudentModel->loadWhere(array('student_uid' => $this->_uid));

		if (isError($student))
			$this->terminateWithJsonError(getError($student));

		$student = getData($student)[0];

		$massnahme = $this->_ci->InternatmassnahmeModel->loadWhere(array('massnahme_id' => $massnahmePost, 'aktiv' => true));

		if (isError($massnahme))
			$this->terminateWithJsonError(getError($massnahme));

		if (!hasData($massnahme))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		$massnahme = getData($massnahme)[0];

		$studiensemester = $this->_ci->StudiensemesterModel->load(array('studiensemester_kurzbz' => $studiensemesterPost));

		if (isError($studiensemester))
			$this->terminateWithJsonError(getError($studiensemester));

		if (!hasData($studiensemester))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		$studiensemester = getData($studiensemester)[0];

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
			$this->terminateWithJsonError(getError($insert));

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
			$this->terminateWithJsonError(getError($insertStatus));

		$this->outputJsonSuccess(array
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
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));

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
				$this->terminateWithJsonError(getError($updateZuordnung));

			$deleteFile = $this->_ci->dmslib->delete($massnahmenZuordnung->person_id, $massnahmenZuordnung->dms_id);

			if (isError($deleteFile))
				$this->terminateWithJsonError(getError($deleteFile));

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
				$this->terminateWithJsonError(getError($insertStatus));

			$this->outputJsonSuccess($massnahmenZuordnung->massnahme_zuordnung_id);
		}
	}

	public function studentDeleteMassnahme()
	{
		$massnahmenZuordnungPost = $this->_ci->input->post('massnahmenZuordnung');

		if (isEmptyString($massnahmenZuordnungPost))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));

		$massnahmenZuordnung = $this->_checkMassnahmenZuordnung($massnahmenZuordnungPost);

		/*
		 * Solang die Maßnahme nicht bestätigt wurde kann sie gelöscht werden
		 */
		if ($massnahmenZuordnung->massnahme_status_kurzbz !== 'confirmed' && $massnahmenZuordnung->massnahme_status_kurzbz !== 'declined')
		{
			$deleteStatus = $this->_ci->InternatmassnahmezuordnungstatusModel->delete(array('massnahme_zuordnung_id' => $massnahmenZuordnung->massnahme_zuordnung_id));

			if (isError($deleteStatus))
				$this->terminateWithJsonError(getError($deleteStatus));

			$deleteZuordnung = $this->_ci->InternatmassnahmezuordnungModel->delete(array('massnahme_zuordnung_id' => $massnahmenZuordnung->massnahme_zuordnung_id));

			if (isError($deleteZuordnung))
				$this->terminateWithJsonError(getError($deleteZuordnung));

			if (!is_null($massnahmenZuordnung->dms_id))
			{
				$deleteFile = $this->_ci->dmslib->delete($massnahmenZuordnung->person_id, $massnahmenZuordnung->dms_id);

				if (isError($deleteFile))
					$this->terminateWithJsonError(getError($deleteFile));
			}

			$this->outputJsonSuccess(getData($deleteZuordnung));
		}
	}

	public function studentAddNachweis()
	{
		$massnahmenZuordnungPost = $this->_ci->input->post('massnahmenZuordnung');

		if (empty($_FILES['file']['name']) || isEmptyString($massnahmenZuordnungPost))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));

		$massnahme = $this->_checkMassnahmenZuordnung($massnahmenZuordnungPost);

		if ($massnahme->massnahme_status_kurzbz !== 'accepted')
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimSpeichern'));

		$dmsFile = $this->_uploadFile();

		if (isError($dmsFile))
			$this->terminateWithJsonError(getError($dmsFile));

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
			$this->terminateWithJsonError(getError($update));

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
			$this->terminateWithJsonError(getError($insertStatus));

		$this->outputJsonSuccess(array('dms_id' => $dmsFile['dms_id'], 'massnahme' => getData($update)['massnahme_zuordnung_id']));

	}

	private function _checkMassnahmenZuordnung($massnahmenZuordnungID)
	{
		$student = $this->_ci->StudentModel->loadWhere(array('student_uid' => $this->_uid));

		if (isError($student))
			$this->terminateWithJsonError(getError($student));

		$student = getData($student)[0];

		$massnahmenZuordnung = $this->_ci->InternatmassnahmezuordnungModel->getMassnahmenWithZuordnung($student->prestudent_id, $massnahmenZuordnungID);

		if (isError($massnahmenZuordnung))
			$this->terminateWithJsonError(getError($massnahmenZuordnung));

		if (!hasData($massnahmenZuordnung))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

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

	public function studentDownloadNachweis()
	{
		$massnahmenZuordnungGet = $this->_ci->input->get('massnahmenZuordnung');

		if (isEmptyString($massnahmenZuordnungGet))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));

		$massnahmenZuordnung = $this->_checkMassnahmenZuordnung($massnahmenZuordnungGet);

		$file = $this->_ci->dmslib->download($massnahmenZuordnung->dms_id, 'Massnahmenbestaetigung.pdf', 'attachment');

		$this->outputFile(getData($file));
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