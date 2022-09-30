<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class InternationalStudentJob extends JOB_Controller
{

	public function __construct()
	{
		parent::__construct();

		$this->load->helper('hlp_sancho_helper');
		$this->load->helper('hlp_language');
		$this->load->model('crm/Student_model', 'StudentModel');

	}

	public function sendMail($days = 7)
	{
		$this->logInfo('Start International Student Job');

		$massnahmen = $this->_getStudentsWithMassnahme($days);

		if (!hasData($massnahmen))
		{
			$this->logInfo('Aborted: No new International status found.');
			exit;
		}

		$massnahmen = getData($massnahmen);

		$students = array_unique(array_column($massnahmen, 'student_uid'));

		foreach ($students as $student)
		{
			$content = $this->_getContent($student, $massnahmen);

			$this->StudentModel->addJoin('public.tbl_prestudent', 'prestudent_id');
			$this->StudentModel->addJoin('public.tbl_person', 'tbl_prestudent.person_id = tbl_person.person_id');
			$student_info = getData($this->StudentModel->load(array('student_uid' => $student)))[0];

			$mail = $student . '@technikum-wien.at';
			$body_fields = array(
				'vorname' => $student_info->vorname,
				'datentabelle' => $content,
				'link' => anchor(site_url('extensions/FHC-Core-International/Student'), 'International Skills Übersicht')
			);

			// Send mail
			sendSanchoMail(
				'InternationalStudentOverview',
				$body_fields,
				$mail,
				'International Skills: Status Änderungen'
			);
		}

		$this->logInfo('End International Job');
	}

	private function _getStudentsWithMassnahme($days)
	{

		$language = getUserLanguage() == 'German' ? 0 : 1;

		$qry = "
			SELECT 
				student.student_uid,
				array_to_json(status.bezeichnung_mehrsprachig::varchar[])->>" . $language ." AS status,
				array_to_json(massnahme.bezeichnung_mehrsprachig::varchar[])->>" . $language ." AS bezeichnung
			FROM extension.tbl_internat_massnahme_zuordnung zuordnung
				JOIN extension.tbl_internat_massnahme massnahme USING (massnahme_id)
				JOIN extension.tbl_internat_massnahme_zuordnung_status zstatus 
					ON(zuordnung.massnahme_zuordnung_id = zstatus.massnahme_zuordnung_id
						AND zstatus.massnahme_zuordnung_status_id = (
							SELECT massnahme_zuordnung_status_id 
							FROM extension.tbl_internat_massnahme_zuordnung_status sstatus
							JOIN extension.tbl_internat_massnahme_zuordnung szuordnung USING (massnahme_zuordnung_id)
							WHERE szuordnung.massnahme_zuordnung_id = zstatus.massnahme_zuordnung_id
							ORDER BY massnahme_zuordnung_status_id DESC LIMIT 1
						)
					)
				JOIN tbl_prestudent prestudent ON zuordnung.prestudent_id = prestudent.prestudent_id
				JOIN tbl_student student ON prestudent.prestudent_id = student.prestudent_id
				JOIN extension.tbl_internat_massnahme_status status USING (massnahme_status_kurzbz)
			WHERE zstatus.insertamum::date > (NOW() - INTERVAL '". $days . " DAYS')::DATE
				AND zstatus.insertvon != student.student_uid
		";


		$db = new DB_Model();

		return $db->execReadOnlyQuery($qry);
	}

	private function _getContent($student, $massnahmen)
	{
		$html = '<table border="1"><tbody>';

		$massnahmen = array_filter($massnahmen,
			function ($status) use (&$student) {
				return $status->student_uid == $student;
			});

		$html .= '<tr>
					<th>UID</th>
					<th>Maßnahme</th>
					<th>Neuer Status</th>
				</tr>';
		foreach ($massnahmen as $massnahme)
		{
			$html .= '<tr>
						<td>'. $massnahme->student_uid .'</td>
						<td>'. $massnahme->bezeichnung .'</td>
						<td>'. $massnahme->status .'</td>
					</tr>';
		}

		$html .= '</tbody></table>';

		return $html;
	}
}


