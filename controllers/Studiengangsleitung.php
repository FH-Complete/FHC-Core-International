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
				'setStatus' => self::BERECHTIGUNG_KURZBZ .':r',
				'download' => self::BERECHTIGUNG_KURZBZ.':r',
				'getAktStudiensemester' => self::BERECHTIGUNG_KURZBZ.':r',
				'getStudents' => self::BERECHTIGUNG_KURZBZ.':r'
			)
		);

		$this->_ci =& get_instance();

		$this->loadPhrases(
			array(
				'global',
				'ui',
				'international',
				'lehre',
				'person'
			)
		);

		$this->load->library('WidgetLib');
		$this->load->library('DmsLib');
		$this->load->library('PhrasesLib');
		$this->load->library('PermissionLib');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahmezuordnung_model', 'InternatmassnahmezuordnungModel');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahmezuordnungstatus_model', 'InternatmassnahmezuordnungstatusModel');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahmestatus_model', 'InternatmassnahmestatusModel');

		$this->_ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');

		$this->setControllerId(); // sets the controller id
		$this->_setAuthUID();
	}

	public function index()
	{
		$oeKurzbz = $this->_getOes();

		$data = [
			'oeKurz' => implode('\',\'', $oeKurzbz)
		];

		$this->_ci->load->view('extensions/FHC-Core-International/studiengangsleitung/studiengangsleitung.php', $data);
	}

	public function setStatus()
	{
		//Die Berechtigung wird hier nochmals abgeprüft, damit man, wenn man nur Lesezugriff hat, kein "Generic Error" zurückbekommt
		if (!$this->_ci->permissionlib->isBerechtigt(self::BERECHTIGUNG_KURZBZ, 'suid'))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'keineBerechtigung'));

		$massnahmeZuordnungPost = $this->_ci->input->post('massnahme_id');
		$statusPost = $this->_ci->input->post('status');
		$absagePost = $this->_ci->input->post('absagegrund');

		if (isEmptyString($massnahmeZuordnungPost))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));

		if (isEmptyString($absagePost))
			$absagePost = null;

		$massnahmeZuordnung = $this->_checkMassnahmenZuordnung($massnahmeZuordnungPost);

		$status = $this->checkStatus($massnahmeZuordnung->massnahme_status_kurzbz, $statusPost);

		$statusKurz = $status->massnahme_status_kurzbz;

		$insertStatus = $this->_ci->InternatmassnahmezuordnungstatusModel->insert(
			array(
				'massnahme_zuordnung_id' => $massnahmeZuordnung->massnahme_zuordnung_id,
				'datum' => date ('Y-m-d'),
				'massnahme_status_kurzbz' => $statusKurz,
				'insertamum' => date('Y-m-d H:i:s'),
				'insertvon' => $this->_uid
			)
		);

		if (isError($insertStatus))
			$this->terminateWithJsonError(getError($insertStatus));

		if ($statusPost === 'declined' && !isEmptyString($absagePost))
		{
			$updateStatus = $this->_ci->InternatmassnahmezuordnungModel->update(
				array('massnahme_zuordnung_id' => $massnahmeZuordnung->massnahme_zuordnung_id),
				array
				(
					'anmerkung_stgl' => $absagePost,
					'updateamum' => date('Y-m-d H:i:s'),
					'updatevon' => $this->_uid
				)
			);

			if (isError($updateStatus))
				$this->terminateWithJsonError(getError($updateStatus));
		};
		$language = getUserLanguage() == 'German' ? 0 : 1;

		$this->sendMail($massnahmeZuordnung->massnahme_zuordnung_id);
		$this->outputJsonSuccess(array('massnahme' => $massnahmeZuordnung->massnahme_zuordnung_id, 'status' => $statusKurz, 'dms_id' => $massnahmeZuordnung->dms_id, 'status_bezeichnung' => $status->bezeichnung_mehrsprachig[$language], 'anmerkung_stgl' => $absagePost));
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

	public function getAktStudiensemester()
	{
		$studiensemester = getData($this->_ci->StudiensemesterModel->getLastOrAktSemester())[0]->studiensemester_kurzbz;

		$this->outputJsonSuccess($studiensemester);
	}

	public function getStudents()
	{
		$status = $this->_ci->input->get('status');
		$ects = $this->_ci->input->get('ects');
		$more = $this->_ci->input->get('more') === 'true';
		$exists = $this->_ci->input->get('exists') === 'true';

		$oeKurzbz = $this->_getOes();

		$result = $this->_ci->InternatmassnahmezuordnungModel->getStudentUIDs($oeKurzbz);

		$result = getData($result);

		$students = [];
		foreach ($result as $res)
		{
			if (!array_search($res->massnahme_status_kurzbz, $status))
			{
				if ($more && !$exists)
					$students[$res->student_uid] = $ects;
				else if (!$more && !$exists)
					$students[$res->student_uid] = 1;
			}
			if (in_array($res->massnahme_status_kurzbz, $status))
			{
				if (!isset($students[$res->student_uid]))
					$students[$res->student_uid] = (int)$res->sum;
				else
					$students[$res->student_uid] += (int)$res->sum;
			}
		}

		$filterStudent = [];
		foreach ($students as $student => $ect)
		{
			if ($more && $ect >= $ects)
				$filterStudent[] = $student;
			elseif (!$more && $ect < $ects)
				$filterStudent[] = $student;
		}

		if (!is_null($filterStudent))
		{
			$this->outputJsonSuccess($filterStudent);
		}
		else
			$this->outputJsonSuccess(null);
	}

	private function sendMail($massnahme_zuordnung_id)
	{
		$massnahmeZuordnung = $this->_checkMassnahmenZuordnung($massnahme_zuordnung_id);

		$this->load->helper('hlp_sancho');

		$mail = $massnahmeZuordnung->student_uid . '@technikum-wien.at';
		
		//Wenn die Bestätigung widerrufen wurde, damit in der Mail der Status "durchgeführt" beim Studierenden nicht auftaucht.
		if ($massnahmeZuordnung->massnahme_status_kurzbz === 'performed')
		{
			$status = 'widerrufen';
			$status_englisch = 'revoked';
		}
		else
		{
			$status = $massnahmeZuordnung->status_bezeichnung_both[0];
			$status_englisch = $massnahmeZuordnung->status_bezeichnung_both[1];
		}

		$body_fields = array(
			'vorname' => $massnahmeZuordnung->vorname,
			'nachname' => $massnahmeZuordnung->nachname,
			'massnahme' => $massnahmeZuordnung->bezeichnung_both[0],
			'massnahme_eng' => $massnahmeZuordnung->bezeichnung_both[1],
			'status' => $status,
			'status_eng' => $status_englisch,
			'link' => anchor(site_url('extensions/FHC-Core-International/Student'), 'International Skills')
		);

		$vorlage = 'InternationalStudentOverview';

		if ($massnahmeZuordnung->massnahme_status_kurzbz === 'confirmed')
			$vorlage = 'InternationalStudentOverviewConf';
		else if($massnahmeZuordnung->massnahme_status_kurzbz === 'accepted')
			$vorlage = 'InternationalStudentOverviewPlan';

		// Send mail
		sendSanchoMail(
			$vorlage,
			$body_fields,
			$mail,
			'International Skills: Update'
		);
	}

	private function _checkMassnahmenZuordnung($massnahmeZuordnungPost)
	{
		$oeKurzbz = $this->_getOes();

		$massnahmeZuordnung = $this->_ci->InternatmassnahmezuordnungModel->getMassnahmeStudiengangsleitung($massnahmeZuordnungPost, $oeKurzbz);

		if (isError($massnahmeZuordnung))
			$this->terminateWithJsonError(getError($massnahmeZuordnung));

		if (!hasData($massnahmeZuordnung))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		return getData($massnahmeZuordnung)[0];
	}

	private function checkStatus($currStatus, $newStatus)
	{
		$newStatus = $this->_ci->InternatmassnahmestatusModel->loadWhere(array('massnahme_status_kurzbz' => $newStatus));

		if (isError($newStatus))
			$this->terminateWithJsonError(getError($newStatus));

		if (!hasData($newStatus))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		$newStatus = getData($newStatus)[0];

		$newStatusKurz = $newStatus->massnahme_status_kurzbz;
		if (($newStatusKurz === $currStatus) ||
			($newStatusKurz === 'declined' && !in_array($currStatus, array('planned', 'performed', 'accepted'))) ||
			($newStatusKurz === 'accepted' && !in_array($currStatus, array('planned', 'performed', 'confirmed'))) ||
			($newStatusKurz === 'confirmed' && $currStatus !== 'performed') ||
			($newStatusKurz === 'performed' && $currStatus !== 'confirmed') ||
			(!in_array($newStatusKurz, array('accepted', 'confirmed', 'declined', 'performed'))))
		{
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimSpeichern'));
		}

		return $newStatus;
	}

	private function _getOes()
	{
		$oeKurzbz = $this->_ci->permissionlib->getOE_isEntitledFor(self::BERECHTIGUNG_KURZBZ);

		if (!$oeKurzbz)
			show_error('You are not allowed to access to this content');

		return $oeKurzbz;
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