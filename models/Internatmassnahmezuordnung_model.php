<?php

class Internatmassnahmezuordnung_model extends DB_Model
{

	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_internat_massnahme_zuordnung';
		$this->pk = array('massnahme_zuordnung_id');
		$this->hasSequence = true;
	}

	public function getMassnahmenWithZuordnung($student, $massnahmenZuordnung)
	{
		$query = '
			SELECT zstatus.massnahme_status_kurzbz,
					zuordnung.massnahme_zuordnung_id,
					zuordnung.dms_id,
					person.person_id
			FROM extension.tbl_internat_massnahme_zuordnung zuordnung
				JOIN public.tbl_prestudent prestudent ON zuordnung.prestudent_id = prestudent.prestudent_id
				JOIN public.tbl_person person ON prestudent.person_id = person.person_id
				JOIN extension.tbl_internat_massnahme massnahme USING(massnahme_id)
				JOIN extension.tbl_internat_massnahme_zuordnung_status status
					ON (zuordnung.massnahme_zuordnung_id = status.massnahme_zuordnung_id)
					AND (status.massnahme_zuordnung_status_id = (SELECT sstatus.massnahme_zuordnung_status_id
											  FROM extension.tbl_internat_massnahme_zuordnung_status sstatus
											  JOIN extension.tbl_internat_massnahme_zuordnung szuordnung USING (massnahme_zuordnung_id)
											  WHERE szuordnung.massnahme_zuordnung_id = status.massnahme_zuordnung_id ORDER BY sstatus.massnahme_zuordnung_status_id DESC LIMIT 1)
						)
				JOIN extension.tbl_internat_massnahme_status zstatus USING(massnahme_status_kurzbz)
			WHERE zuordnung.prestudent_id = ? AND zuordnung.massnahme_zuordnung_id = ?';

		return $this->execQuery($query, array($student, $massnahmenZuordnung));
	}

	public function getMassnahmeStudiengangsleitung($zuordnung, $oe)
	{
		$language = getUserLanguage() == 'German' ? 0 : 1;
		$query = '
			SELECT person.person_id, 
					status.massnahme_status_kurzbz, 
					zuordnung.massnahme_zuordnung_id,
					zuordnung.dms_id,
					array_to_json(massnahme.bezeichnung_mehrsprachig::varchar[])->>' . $language .' AS bezeichnung,
					student.student_uid,
					array_to_json(zstatus.bezeichnung_mehrsprachig::varchar[])->>' . $language . ' AS status_bezeichnung,
					zstatus.bezeichnung_mehrsprachig AS status_bezeichnung_both,
					massnahme.bezeichnung_mehrsprachig AS bezeichnung_both,
					person.vorname,
					person.nachname
			FROM extension.tbl_internat_massnahme_zuordnung zuordnung
				JOIN extension.tbl_internat_massnahme massnahme USING(massnahme_id)
				JOIN extension.tbl_internat_massnahme_zuordnung_status status ON zuordnung.massnahme_zuordnung_id = status.massnahme_zuordnung_id
				AND (status.massnahme_zuordnung_status_id = (SELECT sstatus.massnahme_zuordnung_status_id
											  FROM extension.tbl_internat_massnahme_zuordnung_status sstatus
											  JOIN extension.tbl_internat_massnahme_zuordnung szuordnung USING (massnahme_zuordnung_id)
											  WHERE szuordnung.massnahme_zuordnung_id = status.massnahme_zuordnung_id ORDER BY sstatus.massnahme_zuordnung_status_id DESC LIMIT 1)
						)
				JOIN extension.tbl_internat_massnahme_status zstatus ON status.massnahme_status_kurzbz = zstatus.massnahme_status_kurzbz
				JOIN public.tbl_prestudent prestudent ON prestudent.prestudent_id = zuordnung.prestudent_id
				JOIN public.tbl_student student ON student.prestudent_id = prestudent.prestudent_id
				JOIN public.tbl_person person ON person.person_id = prestudent.person_id
				JOIN public.tbl_studiengang studiengang ON studiengang.studiengang_kz = prestudent.studiengang_kz 
			WHERE zuordnung.massnahme_zuordnung_id = ? AND oe_kurzbz IN ?
		';

		return $this->execQuery($query, array($zuordnung, $oe));
	}

	public function getStudentUIDs($oe)
	{
		$query = 'SELECT
						student.student_uid,
						SUM(ects),
						status.massnahme_status_kurzbz
					FROM extension.tbl_internat_massnahme_zuordnung zuordnung
							JOIN extension.tbl_internat_massnahme massnahme USING(massnahme_id)
							JOIN extension.tbl_internat_massnahme_zuordnung_status status ON zuordnung.massnahme_zuordnung_id = status.massnahme_zuordnung_id
						AND (status.massnahme_zuordnung_status_id = (SELECT sstatus.massnahme_zuordnung_status_id
																	 FROM extension.tbl_internat_massnahme_zuordnung_status sstatus
																	JOIN extension.tbl_internat_massnahme_zuordnung szuordnung USING (massnahme_zuordnung_id)
																	 WHERE szuordnung.massnahme_zuordnung_id = status.massnahme_zuordnung_id ORDER BY sstatus.massnahme_zuordnung_status_id DESC LIMIT 1))
							JOIN extension.tbl_internat_massnahme_status zstatus ON status.massnahme_status_kurzbz = zstatus.massnahme_status_kurzbz
							JOIN public.tbl_prestudent prestudent ON prestudent.prestudent_id = zuordnung.prestudent_id
							JOIN public.tbl_student student ON student.prestudent_id = prestudent.prestudent_id
							JOIN public.tbl_person person ON person.person_id = prestudent.person_id
							JOIN public.tbl_studiengang studiengang ON studiengang.studiengang_kz = prestudent.studiengang_kz
					WHERE oe_kurzbz IN ? AND status.massnahme_status_kurzbz != ?
					GROUP BY student.student_uid, status.massnahme_status_kurzbz';


		return $this->execQuery($query, array($oe, 'declined'));
	}
}
