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
				'studentDownloadNachweis' => self::BERECHTIGUNG_KURZBZ .':rw',
			)
		);

		$this->_ci =& get_instance();
		$this->loadPhrases(
			array(
				'ui',
				'international',
			)
		);

		$this->load->library('DmsLib');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahme_model', 'InternatmassnahmeModel');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahmezuordnung_model', 'InternatmassnahmezuordnungModel');
		$this->_ci->load->model('crm/Student_model', 'StudentModel');
		$this->_ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');

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
			show_error(getError($student));

		if (!hasData($student))
			show_error($this->_ci->p->t('international', 'nurBachelor'));

		$student = getData($student)[0];
		if ($student->typ !== 'b' || in_array($student->studiengang_kz, $this->_ci->config->item('stg_kz_blacklist')))
			show_error($this->_ci->p->t('international', 'nurBachelor'));

		$this->_ci->InternatmassnahmeModel->addOrder('ects');
		$this->_ci->InternatmassnahmeModel->addSelect('massnahme_id,
														ects,
														einmalig,
														array_to_json(bezeichnung_mehrsprachig::varchar[])->>'.$this->language.' as bezeichnung,
														array_to_json(beschreibung_mehrsprachig::varchar[])->>'.$this->language.' as beschreibung');
		$massnahmen = $this->_ci->InternatmassnahmeModel->loadWhere(array('aktiv' => true));

		if (isError($massnahmen))
			show_error(getError($massnahmen));

		$massnahmen = getData($massnahmen);

		$this->_ci->StudentModel->addLimit(1);
		$this->_ci->StudentModel->addOrder('public.tbl_prestudentstatus.datum', 'DESC');
		$this->_ci->StudentModel->addOrder('public.tbl_prestudentstatus.insertamum', 'DESC');
		$this->_ci->StudentModel->addOrder('public.tbl_prestudentstatus.ext_id', 'DESC');
		$this->_ci->StudentModel->addSelect('ausbildungssemester');
		$this->_ci->StudentModel->addJoin('public.tbl_prestudentstatus', 'prestudent_id');
		$ausbildungssemester = $this->_ci->StudentModel->loadWhere(array(
			'student_uid' => $this->_uid,
			'status_kurzbz' => 'Student'
		));

		if (isError($ausbildungssemester))
			show_error(getError($ausbildungssemester));

		$ausbildungssemester = getData($ausbildungssemester)[0]->ausbildungssemester;

		$this->_ci->StudentModel->addSelect('max_semester');
		$this->_ci->StudentModel->addJoin('public.tbl_studiengang', 'studiengang_kz');
		$maxsemester = $this->_ci->StudentModel->load(array('student_uid' => $this->_uid));

		if (isError($maxsemester))
			show_error(getError($maxsemester));

		$maxsemester = getData($maxsemester)[0]->max_semester;

		$diff = $maxsemester - $ausbildungssemester;

		$aktSemester = $this->_ci->StudiensemesterModel->getAktOrNextSemester();
		$this->_ci->StudiensemesterModel->addLimit($diff + 1);
		$this->_ci->StudiensemesterModel->addOrder('start');
		$studiensemester = $this->_ci->StudiensemesterModel->loadWhere(array('start >=' => getData($aktSemester)[0]->start));
		if (isError($studiensemester))
			show_error(getError($studiensemester));

		$studiensemester = getData($studiensemester);

		$this->_ci->load->view('extensions/FHC-Core-International/cis/student.php',
			array('massnahmen' => $massnahmen,
				'studiensemester' => $studiensemester)
		);
	}

	private function _checkMassnahmenZuordnung($massnahmenZuordnungID)
	{
		$student = $this->_ci->StudentModel->loadWhere(array('student_uid' => $this->_uid));

		if (isError($student))
			show_error(getError($student));

		$student = getData($student)[0];

		$massnahmenZuordnung = $this->_ci->InternatmassnahmezuordnungModel->getMassnahmenWithZuordnung($student->prestudent_id, $massnahmenZuordnungID);

		if (isError($massnahmenZuordnung))
			show_error(getError($massnahmenZuordnung));

		if (!hasData($massnahmenZuordnung))
			show_error($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		return getData($massnahmenZuordnung)[0];
	}

	public function studentDownloadNachweis()
	{
		$massnahmenZuordnungGet = $this->_ci->input->get('massnahmenZuordnung');

		if (isEmptyString($massnahmenZuordnungGet))
			show_error($this->_ci->p->t('ui', 'fehlerBeimLesen'));

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