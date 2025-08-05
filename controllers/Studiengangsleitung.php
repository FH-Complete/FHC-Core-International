<?php

class Studiengangsleitung extends Auth_Controller
{

	private $_ci; // Code igniter instance

	private $_uid;

	const BERECHTIGUNG_KURZBZ = 'extension/internationalReview';

	const NOTE = 16;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
				'index' => self::BERECHTIGUNG_KURZBZ .':r',
				'setStatus' => self::BERECHTIGUNG_KURZBZ .':rw',
				'setNote' => self::BERECHTIGUNG_KURZBZ .':rw',
				'download' => self::BERECHTIGUNG_KURZBZ.':r',
				'getStudents' => self::BERECHTIGUNG_KURZBZ.':r',
				'getLVs' => self::BERECHTIGUNG_KURZBZ.':r',
				'load' => self::BERECHTIGUNG_KURZBZ.':r',
				'loadBenotungen' => self::BERECHTIGUNG_KURZBZ.':r',
				'getOrgForms' => self::BERECHTIGUNG_KURZBZ.':r',
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
		$this->_ci->load->model('organisation/Studiengang_model', 'StudiengangModel');
		$this->_ci->load->model('organisation/Studienplan_model', 'StudienplanModel');
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->_ci->load->model('education/Lvgesamtnote_model', 'LvgesamtnoteModel');
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

		$aktStsem = $this->_ci->StudiensemesterModel->getLastOrAktSemester();

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
	public function load()
	{
		$studiengang = $this->_ci->input->get('stg');

		if (isEmptyString($studiengang))
			return $this->outputJsonSuccess('');

		$stgBerechtigung = $this->_ci->permissionlib->getSTG_isEntitledFor(self::BERECHTIGUNG_KURZBZ);

		if (!in_array($studiengang, $stgBerechtigung))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'keineBerechtigung'));

		$this->outputJsonSuccess($this->_ci->InternatmassnahmezuordnungModel->getDataStudiengangsleitung(array($studiengang)));
	}

	public function loadBenotungen()
	{
		$studiengang = $this->_ci->input->get('stg');
		$studiensemester = $this->_ci->input->get('stsem');
		if (isEmptyString($studiengang) || isEmptyString($studiensemester))
			return $this->outputJsonSuccess('');

		$stgBerechtigung = $this->_ci->permissionlib->getSTG_isEntitledFor(self::BERECHTIGUNG_KURZBZ);

		if (!in_array($studiengang, $stgBerechtigung))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'keineBerechtigung'));

		$this->outputJson($this->_ci->InternatmassnahmezuordnungModel->getDataStudiengangsleitungBenotung($studiengang, $studiensemester));
	}

	public function getLVs()
	{
		$studiengang = $this->_ci->input->get('stg');
		$studiensemester = $this->_ci->input->get('stsem');
		if (isEmptyString($studiengang) || isEmptyString($studiensemester))
			return $this->outputJsonSuccess('');

		$stgBerechtigung = $this->_ci->permissionlib->getSTG_isEntitledFor(self::BERECHTIGUNG_KURZBZ);

		if (!in_array($studiengang, $stgBerechtigung))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'keineBerechtigung'));

		$this->_ci->LehrveranstaltungModel->addSelect('bezeichnung, lehrveranstaltung_id, studiengang_kz, orgform_kurzbz');
		$lvs = $this->_ci->LehrveranstaltungModel->loadWhere(
			"zeugnis AND studiengang_kz = " . $studiengang ."
			AND semester = (SELECT max_semester
							FROM tbl_studiengang
							WHERE tbl_studiengang.studiengang_kz = tbl_lehrveranstaltung.studiengang_kz)
		");

		$this->outputJsonSuccess(hasData($lvs) ? getData($lvs) : []);
	}

	public function getOrgForms()
	{
		$studiengang = $this->_ci->input->get('stg');
		$studiensemester = $this->_ci->input->get('stsem');
		if (isEmptyString($studiengang) || isEmptyString($studiensemester))
			return $this->outputJsonSuccess('');

		$stgBerechtigung = $this->_ci->permissionlib->getSTG_isEntitledFor(self::BERECHTIGUNG_KURZBZ);

		if (!in_array($studiengang, $stgBerechtigung))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'keineBerechtigung'));

		$this->_ci->StudienplanModel->addSelect('DISTINCT(tbl_studienplan.orgform_kurzbz)');
		$this->_ci->StudienplanModel->addJoin('lehre.tbl_studienordnung', 'studienordnung_id');
		$this->_ci->StudienplanModel->addJoin('lehre.tbl_studienplan_semester', 'studienplan_id');
		$this->_ci->StudienplanModel->addJoin('public.tbl_studiengang', 'tbl_studienordnung.studiengang_kz = tbl_studiengang.studiengang_kz');
		$this->_ci->StudienplanModel->addOrder('tbl_studienplan.orgform_kurzbz');
		$orgformen = $this->_ci->StudienplanModel->loadWhere(
			"tbl_studiengang.studiengang_kz = " . $studiengang ."
			AND studiensemester_kurzbz = '" . $studiensemester ."'
		");

		$this->outputJsonSuccess(hasData($orgformen) ? getData($orgformen) : []);
	}

	private function _setStatusMulti($data)
	{
		$language = getUserLanguage() == 'German' ? 0 : 1;
		$status_bezeichnung = '';
		$statusKurz = '';

		foreach ($data as $massnahme)
		{
			if (isset($massnahme->massnahme_id) && isset($massnahme->status))
			{
				$massnahmeZuordnung = $this->_checkMassnahmenZuordnung($massnahme->massnahme_id);

				$status = $this->checkStatus($massnahmeZuordnung->massnahme_status_kurzbz, $massnahme->status);
				$statusKurz = $status->massnahme_status_kurzbz;
				$status_bezeichnung = $status->bezeichnung_mehrsprachig[$language];

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

				$this->sendMail($massnahmeZuordnung->massnahme_zuordnung_id);
			}
		}
		$this->outputJsonSuccess(array('status_bezeichnung' => $status_bezeichnung, 'statusKurz' => $statusKurz));
	}

	public function setStatus()
	{
		$postJson = $this->getPostJSON();

		if (is_array($postJson))
			return $this->_setStatusMulti($postJson);

		if (!property_exists($postJson, 'massnahme_id'))
			$this->terminateWithJsonError('Error: Massnahme_id missing');

		if (!property_exists($postJson, 'status'))
			$this->terminateWithJsonError('Error: status missing');

		$massnahmeZuordnungPost = $postJson->massnahme_id;
		$statusPost = $postJson->status;
		$absagePost = isset($postJson->absageGrund) ? $postJson->absageGrund : '';

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

	public function setNote()
	{
		$stgBerechtigung = $this->_ci->permissionlib->getSTG_isEntitledFor(self::BERECHTIGUNG_KURZBZ);
		$postJson = $this->getPostJSON();
		if (isEmptyArray($postJson))
			return $this->outputJsonSuccess(0);

		if (!isset($postJson[0]) || !isset($postJson[0]->stg) || !isset($postJson[0]->lv) || !isset($postJson[0]->stsem))
			$this->terminateWithJsonError("Error: Fehler beim Setzen der Note");

		$studiengang = $postJson[0]->stg;
		if (!in_array($studiengang, $stgBerechtigung))
			$this->terminateWithJsonError("Error");

		$lv = $this->_ci->LehrveranstaltungModel->loadWhere(array('lehrveranstaltung_id' => $postJson[0]->lv));

		if (isError($lv) || !hasData($lv))
			$this->terminateWithJsonError("Error");
		$lv = getData($lv)[0];

		$studiensemester = $this->_ci->StudiensemesterModel->loadWhere(array('studiensemester_kurzbz' => $postJson[0]->stsem));

		if (isError($studiensemester) || !hasData($studiensemester))
			$this->terminateWithJsonError("Error");

		$studiensemester_kurzbz = getData($studiensemester)[0]->studiensemester_kurzbz;
		$count = 0;
		$students = [];
		foreach ($postJson as $person)
		{
			if (!isset($person->student_uid) || !isset($person->lv))
				continue;

			$enoughECTs = $this->_ci->InternatmassnahmezuordnungModel->enoughECTs($person->student_uid);

			if (!hasData($enoughECTs))
				continue;

			$hasBenotung = $this->_ci->LvgesamtnoteModel->load(array(
				'student_uid' => $person->student_uid,
				'studiensemester_kurzbz' => $studiensemester_kurzbz,
				'lehrveranstaltung_id' => $person->lv
			));

			if (!hasData($hasBenotung))
			{
				$insertResult = $this->_ci->LvgesamtnoteModel->insert(
					array(
						"lehrveranstaltung_id" => $person->lv,
						"studiensemester_kurzbz" => $studiensemester_kurzbz,
						"student_uid" => $person->student_uid,
						"note" => self::NOTE,
						"mitarbeiter_uid" => $this->_uid,
						"benotungsdatum" =>  date('Y-m-d H:i:s'),
						"insertamum" =>  date('Y-m-d H:i:s'),
						"insertvon" => $this->_uid,
						"freigabedatum" => date('Y-m-d H:i:s'),
						"freigabevon_uid" => $this->_uid
					)
				);

				if (isError($insertResult))
					$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimSpeichern'));

				if (isSuccess($insertResult))
				{
					$count++;
					$students[] = $person->student_uid;
				}
			}
		}
		if (!isEmptyArray($students))
		{
			$studiengang = $this->_ci->StudiengangModel->load($studiengang);

			$studiengang = getData($studiengang)[0];
			$email = $studiengang->email;

			$this->_ci->load->model('ressource/mitarbeiter_model', 'MitarbeiterModel');
			$this->_ci->MitarbeiterModel->addJoin('tbl_benutzer', 'tbl_benutzer.uid = tbl_mitarbeiter.mitarbeiter_uid');
			$this->_ci->MitarbeiterModel->addJoin('tbl_person', 'tbl_benutzer.person_id = tbl_person.person_id');
			$mitarbeiter = $this->_ci->MitarbeiterModel->loadWhere(array('mitarbeiter_uid' => $this->_uid));

			if (hasData($mitarbeiter))
			{
				$mitarbeiter = getData($mitarbeiter)[0];
				$name = $mitarbeiter->anrede . ' ' . $mitarbeiter->vorname . ' ' . $mitarbeiter->nachname . ' (' . $mitarbeiter->kurzbz . ')';
			}
			else
				$name = "";

			$content = $this->_getContent($students);

			$body_fields = array(
				'name' => $name,
				'lv_bezeichnung' => $lv->bezeichnung,
				'lv_orgform_kurzbz' => $lv->orgform_kurzbz,
				'lv_semester' => $lv->semester,
				'stg_bezeichnung' => $studiengang->bezeichnung,
				'stg_kuerzel' => strtoupper($studiengang->typ . $studiengang->kurzbz),
				'semester' => $studiensemester_kurzbz,
				'datentabelle' => $content,
				'count' => count($students)
			);
			$this->load->helper('hlp_sancho');

			sendSanchoMail(
				'InternationalNote',
				$body_fields,
				$email,
				'International Skills: Noten'
			);
		}
		$this->outputJsonSuccess(array('count' => $count, 'students' => $students));
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

	public function getStudents()
	{
		$status = $this->_ci->input->get('status');
		$ects = $this->_ci->input->get('ects');
		$more = $this->_ci->input->get('more') === 'true';
		$exists = $this->_ci->input->get('exists') === 'true';
		$stg = $this->_ci->input->get('stg');

		$stgBerechtigung = $this->_ci->permissionlib->getSTG_isEntitledFor(self::BERECHTIGUNG_KURZBZ);
		if (in_array($stg, $stgBerechtigung))
		{
			$stgStudents = explode(',', $stg);
		}
		else if (isEmptyString($stg))
			$stgStudents = $stgBerechtigung;

		$result = $this->_ci->InternatmassnahmezuordnungModel->getStudentUIDs($stgStudents);

		$result = getData($result);

		$students = [];
		foreach ($result as $res)
		{
			if (!array_search($res->massnahme_status_kurzbz, $status))
			{
				if ($more && !$exists)
				{
					$students[$res->student_uid] = $ects;
				}
				else if (!$more && !$exists)
				{
					$students[$res->student_uid] = 1;
				}
			}
			if (in_array($res->massnahme_status_kurzbz, $status) || (is_null($res->massnahme_status_kurzbz) && !$more))
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
			'anmerkung' => $massnahmeZuordnung->anmerkung_stgl,
			'link' => anchor(site_url('extensions/FHC-Core-International/Student'), 'International Skills')
		);

		$vorlage = 'InternationalStudentOverview';

		if ($massnahmeZuordnung->massnahme_status_kurzbz === 'confirmed')
			$vorlage = 'InternationalStudentOverviewConf';
		else if ($massnahmeZuordnung->massnahme_status_kurzbz === 'accepted')
			$vorlage = 'InternationalStudentOverviewPlan';
		else if ($massnahmeZuordnung->massnahme_status_kurzbz === 'declined')
			$vorlage = 'InternationalStudentOverviewDecl';
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
		$stgBerechtigung = $this->_ci->permissionlib->getSTG_isEntitledFor(self::BERECHTIGUNG_KURZBZ);

		$massnahmeZuordnung = $this->_ci->InternatmassnahmezuordnungModel->getMassnahmeStudiengangsleitung($massnahmeZuordnungPost, $stgBerechtigung);
		
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

	private function _getContent($students)
	{
		$html = '<table border="1"><tbody>';

		$html .= "<tr>
					<th>". $this->p->t('person', 'uid') ."</th>
				</tr>";

		foreach ($students as $student)
		{
			$html .= '<tr>
						<td>'. $student .'</td>
					</tr>';
		}

		$html .= '</tbody></table>';

		return $html;
	}
}