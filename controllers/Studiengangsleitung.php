<?php

class Studiengangsleitung extends Auth_Controller
{

	private $_ci; // Code igniter instance

	private $_uid;

	const BERECHTIGUNG_KURZBZ = 'extension/internationalReview';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
				'index' => self::BERECHTIGUNG_KURZBZ .':r',
				'download' => self::BERECHTIGUNG_KURZBZ.':r',
			)
		);

		$this->_ci =& get_instance();

		$this->loadPhrases(
			array(
				'ui',
			)
		);

		$this->load->library('DmsLib');
		$this->load->library('PermissionLib');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahmezuordnung_model', 'InternatmassnahmezuordnungModel');

		$this->_ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
		$this->_ci->load->model('organisation/Studiengang_model', 'StudiengangModel');
		$this->_ci->load->model('codex/Orgform_model', 'OrgformModel');

		$this->setControllerId(); // sets the controller id
		$this->_setAuthUID();
	}

	public function index()
	{
		$readOnly = !$this->_ci->permissionlib->isBerechtigt(self::BERECHTIGUNG_KURZBZ, 'suid');

		$stgBerechtigung = $this->_ci->permissionlib->getSTG_isEntitledFor(self::BERECHTIGUNG_KURZBZ);

		$this->_ci->StudiengangModel->addOrder('kurzbzlang');
		$this->_ci->StudiengangModel->addSelect('studiengang_kz, kurzbzlang');
		$where = 'studiengang_kz IN (\'' . implode('\',\'', $stgBerechtigung) . '\')';
		$studiengaenge = $this->_ci->StudiengangModel->loadWhere($where);

		$aktStsem = $this->_ci->StudiensemesterModel->getAkt();

		$this->_ci->StudiensemesterModel->addOrder('start', 'DESC');
		$studiensemester = $this->_ci->StudiensemesterModel->load();


		$data = [
			'studiengaenge' => getData($studiengaenge),
			'studiensemester' => getData($studiensemester),
			'readOnly' => $readOnly,
			'aktstsem' => getData($aktStsem)[0]->studiensemester_kurzbz
		];

		$this->_ci->load->view('extensions/FHC-Core-International/studiengangsleitung/studiengangsleitung.php', $data);
	}

	public function download()
	{
		$massnahmeZuordnungPost = $this->_ci->input->get('massnahme');

		if (isEmptyString($massnahmeZuordnungPost))
			show_error('Missing correct parameter');

		$massnahmeZuordnung = $this->_checkMassnahmenZuordnung($massnahmeZuordnungPost);

		if (!is_null($massnahmeZuordnung->dms_id))
		{
			$fileName = 'Bestätigung' . '_' . $massnahmeZuordnung->student_uid . '_' .$massnahmeZuordnung->bezeichnung . '.pdf';

			$file = $this->_ci->dmslib->download($massnahmeZuordnung->dms_id, $fileName, 'attachment');

			$this->outputFile(getData($file));
		}
	}



	private function _checkMassnahmenZuordnung($massnahmeZuordnungPost)
	{
		$stgBerechtigung = $this->_ci->permissionlib->getSTG_isEntitledFor(self::BERECHTIGUNG_KURZBZ);

		$massnahmeZuordnung = $this->_ci->InternatmassnahmezuordnungModel->getMassnahmeStudiengangsleitung($massnahmeZuordnungPost, $stgBerechtigung);
		
		if (isError($massnahmeZuordnung))
			$this->terminateWithJsonError(getError($massnahmeZuordnung));

		if (!hasData($massnahmeZuordnung))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		return getData($massnahmeZuordnung)[0];
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