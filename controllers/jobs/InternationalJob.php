<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class InternationalJob extends JOB_Controller
{

	public function __construct()
	{
		parent::__construct();

		$this->load->helper('hlp_sancho_helper');
		$this->load->helper('hlp_language');

		$this->load->model('organisation/Studiengang_model', 'StudiengangModel');
	}

	public function sendMail($days = 7)
	{
		$this->logInfo('Start International Job');

		$massnahmen = $this->_getStudentsWithMassnahme($days);

		if (!hasData($massnahmen))
		{
			$this->logInfo('Aborted: No new International status found.');
			exit;
		}

		$massnahmen = getData($massnahmen);

		$studiengaenge_kz = array_unique(array_column($massnahmen, 'studiengang_kz'));

		foreach ($studiengaenge_kz as $studiengang_kz)
		{
			$stglMails = $this->_getMailAddress($studiengang_kz);

			$content = $this->_getContent($studiengang_kz, $massnahmen);

			$studiengang = $this->StudiengangModel->load($studiengang_kz)->retval[0];

			foreach ($stglMails as $stglMail)
			{
				$body_fields = array(
					'vorname' => $stglMail['vorname'],
					'studiengang' => $studiengang->bezeichnung,
					'datentabelle' => $content,
					'link' => anchor(site_url('extensions/FHC-Core-International/Studiengangsleitung'), 'International Skills Übersicht')
				);

				// Send mail
				sendSanchoMail(
					'InternationalOverview',
					$body_fields,
					$stglMail['to'],
					'International Skills: Status Änderungen'
				);
			}
		}

		$this->logInfo('End International Job');
	}

	private function _getStudentsWithMassnahme($days)
	{
		$language = getUserLanguage() == 'German' ? 0 : 1;

		$qry = "
			SELECT 
				student.student_uid,
				person.vorname,
				person.nachname,
				array_to_json(status.bezeichnung_mehrsprachig::varchar[])->>" . $language ." AS status,
				array_to_json(massnahme.bezeichnung_mehrsprachig::varchar[])->>" . $language ." AS bezeichnung,
				studiengang.studiengang_kz
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
				JOIN tbl_person person ON prestudent.person_id = person.person_id
				JOIN extension.tbl_internat_massnahme_status status USING (massnahme_status_kurzbz)
				JOIN tbl_studiengang studiengang on prestudent.studiengang_kz = studiengang.studiengang_kz
			WHERE zstatus.insertamum::date > (NOW() - INTERVAL '". $days ." DAYS')::DATE
				AND zstatus.insertvon = student.student_uid
		";

		$db = new DB_Model();

		return $db->execReadOnlyQuery($qry);
	}

	private function _getMailAddress($studiengang_kz)
	{
		$stglMails = array();
		$result = $this->StudiengangModel->getLeitung($studiengang_kz);

		// Get STGL mail address
		if (hasData($result))
		{
			foreach (getData($result) as $stgl)
			{
				$stglMails[] = array(
					'to' => $stgl->uid. '@'. DOMAIN,
					'vorname' => $stgl->vorname
				);
			}

			return $stglMails;
		}
		else
		{
			$result = $this->StudiengangModel->load($studiengang_kz);

			if (hasData($result))
			{
				return array(
					$result->retval[0]->email,
					''
				);
			}
		}
	}

	private function _getContent($studiengang_kz, $massnahmen)
	{
		$html = '<table border="1"><tbody>';

		$massnahmen = array_filter($massnahmen,
			function ($status) use (&$studiengang_kz) {
				return $status->studiengang_kz == $studiengang_kz;
			});

		$html .= '<tr>
					<th>UID</th>
					<th>Vorname</th>
					<th>Nachname</th>
					<th>Maßnahme</th>
					<th>Neuer Status</th>
				</tr>';
		foreach ($massnahmen as $massnahme)
		{
			$html .= '<tr>
						<td>'. $massnahme->student_uid .'</td>
						<td>'. $massnahme->vorname .'</td>
						<td>'. $massnahme->nachname .'</td>
						<td>'. $massnahme->bezeichnung .'</td>
						<td>'. $massnahme->status .'</td>
					</tr>';
		}

		$html .= '</tbody></table>';

		return $html;
	}
}


