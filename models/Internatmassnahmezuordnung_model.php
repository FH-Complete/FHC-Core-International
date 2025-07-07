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

	public function getMassnahmeStudiengangsleitung($zuordnung, $stg)
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
					person.nachname,
					zuordnung.anmerkung_stgl
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
			WHERE zuordnung.massnahme_zuordnung_id = ? AND prestudent.studiengang_kz IN ?
		';

		return $this->execQuery($query, array($zuordnung, $stg));
	}

	public function getStudentUIDs($stgs)
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
					WHERE prestudent.studiengang_kz IN ? AND status.massnahme_status_kurzbz != ?
					GROUP BY student.student_uid, status.massnahme_status_kurzbz
					UNION ALL
					SELECT DISTINCT ON(tbl_student.student_uid) tbl_student.student_uid, 0, null
					FROM tbl_studentlehrverband
					JOIN tbl_student ON tbl_studentlehrverband.student_uid = tbl_student.student_uid
					JOIN tbl_prestudent ON tbl_student.prestudent_id = tbl_prestudent.prestudent_id
					WHERE get_rolle_prestudent(tbl_prestudent.prestudent_id, NULL) IN ? AND tbl_prestudent.studiengang_kz IN ? AND tbl_student.prestudent_id NOT IN (
						SELECT DISTINCT(tbl_internat_massnahme_zuordnung.prestudent_id)
						FROM extension.tbl_internat_massnahme_zuordnung
					)
					';


		return $this->execQuery($query, array($stgs, 'declined', array('Student', 'Diplomand'), $stgs));
	}
	public function getDataStudiengangsleitung($stgs)
	{
		$language = getUserLanguage() == 'German' ? 1 : 2;
		$query = '
			WITH gefilterte_lehrveranstaltung AS (
				SELECT lehrveranstaltung_id
				FROM lehre.tbl_lehrveranstaltung
				WHERE bezeichnung = ?
			)
			SELECT zuordnung.massnahme_zuordnung_id,
			student.student_uid,
			studiengang.kurzbzlang AS "studiengang_kurz",
			studiengang.studiengang_kz,
			person.vorname,
			person.nachname,
			massnahme.bezeichnung_mehrsprachig['. $language .'] AS "bezeichnung",
			massnahme.beschreibung_mehrsprachig['. $language .'] AS "beschreibung",
			status.bezeichnung_mehrsprachig['.$language.'] AS "status_bezeichnung",
			status.massnahme_status_kurzbz,
			zuordnung.anmerkung,
			(
				SELECT datum
				FROM extension.tbl_internat_massnahme_zuordnung_status
				WHERE tbl_internat_massnahme_zuordnung_status.massnahme_zuordnung_id = zuordnung.massnahme_zuordnung_id
				ORDER BY massnahme_zuordnung_status_id DESC LIMIT 1
			) as datum,
			zuordnung.anmerkung_stgl as anmerkung_stgl,
			zuordnung.studiensemester_kurzbz AS "studiensemester",
			zuordnung.dms_id AS "document",
			status.massnahme_status_kurzbz AS "akzeptieren",
			status.massnahme_status_kurzbz AS "massnahme_akzeptieren",
			massnahme.ects AS "ects",
			(
				SELECT tbl_studentlehrverband.studiensemester_kurzbz
				FROM tbl_studentlehrverband
				JOIN tbl_studiensemester ON tbl_studentlehrverband.studiensemester_kurzbz = tbl_studiensemester.studiensemester_kurzbz
				WHERE tbl_studentlehrverband.student_uid = student.student_uid
				ORDER BY start DESC LIMIT 1
			) AS "student_studiensemester",
			student.student_uid AS "kontakt",
			studiengang.max_semester as "max_semester",
			(
				SELECT tbl_studentlehrverband.semester
				FROM tbl_studentlehrverband
				JOIN tbl_studiensemester ON tbl_studentlehrverband.studiensemester_kurzbz = tbl_studiensemester.studiensemester_kurzbz
				WHERE tbl_studentlehrverband.student_uid = student.student_uid
				ORDER BY start DESC LIMIT 1
			) AS "semester",
			CASE WHEN note IS NOT NULL THEN true ELSE false END as note,
			(
				SELECT tbl_prestudentstatus.orgform_kurzbz
				FROM public.tbl_prestudentstatus
					LEFT JOIN lehre.tbl_studienplan USING (studienplan_id)
				WHERE prestudent_id = prestudent.prestudent_id
				ORDER BY tbl_prestudentstatus.datum DESC, tbl_prestudentstatus.insertamum DESC, tbl_prestudentstatus.ext_id DESC LIMIT 1
			) as orgform,
			get_rolle_prestudent(prestudent.prestudent_id, NULL) AS "status_kurzbz"
			FROM
		tbl_studentlehrverband
		JOIN tbl_student student ON tbl_studentlehrverband.student_uid = student.student_uid
		JOIN tbl_prestudent prestudent ON prestudent.prestudent_id = student.prestudent_id
		JOIN tbl_person person ON prestudent.person_id = person.person_id
		JOIN tbl_studiengang studiengang on prestudent.studiengang_kz = studiengang.studiengang_kz
		LEFT JOIN extension.tbl_internat_massnahme_zuordnung zuordnung
			JOIN extension.tbl_internat_massnahme massnahme USING (massnahme_id)
			LEFT JOIN extension.tbl_internat_massnahme_zuordnung_status zstatus
				ON(zuordnung.massnahme_zuordnung_id = zstatus.massnahme_zuordnung_id
					AND zstatus.massnahme_zuordnung_status_id = (
						SELECT massnahme_zuordnung_status_id
						FROM extension.tbl_internat_massnahme_zuordnung_status sstatus
							JOIN extension.tbl_internat_massnahme_zuordnung szuordnung USING (massnahme_zuordnung_id)
						WHERE szuordnung.massnahme_zuordnung_id = zstatus.massnahme_zuordnung_id
						ORDER BY massnahme_zuordnung_status_id DESC LIMIT 1
					)
				)
			ON zuordnung.prestudent_id = prestudent.prestudent_id
			LEFT JOIN extension.tbl_internat_massnahme_status status USING (massnahme_status_kurzbz)
			LEFT JOIN public.tbl_studiensemester ON (tbl_studiensemester.studiensemester_kurzbz = zuordnung.studiensemester_kurzbz)
			LEFT JOIN campus.tbl_lvgesamtnote lvgesamtnote ON lvgesamtnote.student_uid = student.student_uid AND lvgesamtnote.lehrveranstaltung_id IN (SELECT lehrveranstaltung_id FROM gefilterte_lehrveranstaltung)
			WHERE get_rolle_prestudent(prestudent.prestudent_id, NULL) IN ?
				AND tbl_studentlehrverband.studiengang_kz IN ?
			GROUP BY  student.student_uid,
				person.person_id,
				prestudent.prestudent_id,
				zuordnung.massnahme_zuordnung_id,
				massnahme.massnahme_id,
				status.massnahme_status_kurzbz,
				studiengang.studiengang_kz,
				note
			ORDER BY studiengang.kurzbzlang,
				student_uid ASC,
			CASE
				WHEN status.massnahme_status_kurzbz = \'planned\' THEN 1
				WHEN status.massnahme_status_kurzbz = \'accepted\' THEN 2
				WHEN status.massnahme_status_kurzbz = \'performed\' THEN 3
				WHEN status.massnahme_status_kurzbz = \'confirmed\' THEN 4
				WHEN status.massnahme_status_kurzbz = \'declined\' THEN 5
			END';
		return $this->execReadOnlyQuery($query, array('International Skills', array('Student', 'Diplomand'), $stgs));
	}
	public function getDataStudiengangsleitungBenotung($stg, $stsem)
	{
		$query = '

				WITH gefilterte_lehrveranstaltung AS (
					SELECT lehrveranstaltung_id
					FROM lehre.tbl_lehrveranstaltung
					WHERE bezeichnung = ?
				),
				letzter_status_massnahme AS (
					SELECT
						zstatus.massnahme_zuordnung_id,
						MAX(zstatus.massnahme_zuordnung_status_id) AS latest_status_id
					FROM extension.tbl_internat_massnahme_zuordnung_status zstatus
					GROUP BY zstatus.massnahme_zuordnung_id
				),
				gefilterte_zuordnung AS (
					SELECT
						zuordnung.massnahme_zuordnung_id,
						zuordnung.prestudent_id,
						massnahme.ects
					FROM extension.tbl_internat_massnahme_zuordnung zuordnung
						JOIN extension.tbl_internat_massnahme massnahme ON zuordnung.massnahme_id = massnahme.massnahme_id
						JOIN letzter_status_massnahme ON zuordnung.massnahme_zuordnung_id = letzter_status_massnahme.massnahme_zuordnung_id
						JOIN extension.tbl_internat_massnahme_zuordnung_status zstatus ON letzter_status_massnahme.latest_status_id = zstatus.massnahme_zuordnung_status_id
					WHERE zstatus.massnahme_status_kurzbz = ?
				),
				student_ects AS (
					SELECT
						tbl_student.student_uid,
						SUM(gefilterte_zuordnung.ects) AS total_ects,
						tbl_prestudent.prestudent_id,
						tbl_prestudent.studiengang_kz
					FROM gefilterte_zuordnung
						JOIN tbl_prestudent ON gefilterte_zuordnung.prestudent_id = tbl_prestudent.prestudent_id
						JOIN tbl_student ON tbl_prestudent.prestudent_id = tbl_student.prestudent_id
					GROUP BY student_uid, tbl_prestudent.prestudent_id, tbl_prestudent.studiengang_kz
					HAVING SUM(gefilterte_zuordnung.ects) >= ?
				),
				letztes_studiensemester AS (
					SELECT DISTINCT ON (prestudent.prestudent_id)
						prestudent.prestudent_id,
						ps.studiensemester_kurzbz
					FROM tbl_prestudent prestudent
						JOIN tbl_prestudentstatus ps ON prestudent.prestudent_id = ps.prestudent_id
					WHERE studiengang_kz = ?
					ORDER BY prestudent.prestudent_id, ps.datum DESC, ps.insertamum DESC, ps.ext_id DESC
				)
				SELECT
					student_ects.student_uid,
					CASE WHEN lvgesamtnote.student_uid IS NOT NULL THEN true ELSE false END AS note
				FROM student_ects
					JOIN letztes_studiensemester lss ON student_ects.prestudent_id = lss.prestudent_id
					JOIN tbl_studentlehrverband slv ON student_ects.student_uid = slv.student_uid
					JOIN tbl_studiengang sg ON student_ects.studiengang_kz = sg.studiengang_kz
					LEFT JOIN campus.tbl_lvgesamtnote lvgesamtnote ON lvgesamtnote.student_uid = student_ects.student_uid AND lvgesamtnote.lehrveranstaltung_id IN (SELECT lehrveranstaltung_id FROM gefilterte_lehrveranstaltung)
				WHERE
					get_rolle_prestudent(student_ects.prestudent_id, NULL) IN ?
					AND sg.max_semester = slv.semester
					AND lss.studiensemester_kurzbz = ?
				GROUP BY
					student_ects.student_uid,
					lvgesamtnote.student_uid;';

		return $this->execReadOnlyQuery($query, array('International Skills', 'confirmed', 5, $stg, array('Student', 'Diplomand'), $stsem));
	}
	
	public function getDataStudent($student, $language)
	{
		$query = 'SELECT
					massnahme.bezeichnung_mehrsprachig['.$language.'] AS "bezeichnung",
					massnahme.ects,
					zuordnung.studiensemester_kurzbz,
					status.bezeichnung_mehrsprachig['.$language.'] AS "status",
					massnahme.beschreibung_mehrsprachig['. $language .'] AS "beschreibung",
					status.massnahme_status_kurzbz,
					zuordnung.anmerkung,
					zuordnung.anmerkung_stgl,
					zuordnung.dms_id,
					zuordnung.massnahme_zuordnung_id,
					massnahme.massnahme_id,
					zuordnung.massnahme_zuordnung_id AS "massnahmen_status"
					FROM extension.tbl_internat_massnahme_zuordnung zuordnung
						JOIN public.tbl_prestudent prestudent ON  zuordnung.prestudent_id = prestudent.prestudent_id
						JOIN public.tbl_student student ON prestudent.prestudent_id = student.prestudent_id
						JOIN public.tbl_person person ON prestudent.person_id = person.person_id
						JOIN extension.tbl_internat_massnahme massnahme USING(massnahme_id)
						JOIN extension.tbl_internat_massnahme_zuordnung_status zstatus
							ON (zuordnung.massnahme_zuordnung_id = zstatus.massnahme_zuordnung_id)
								AND (zstatus.massnahme_zuordnung_status_id =
									(
										SELECT szstatus.massnahme_zuordnung_status_id
										FROM extension.tbl_internat_massnahme_zuordnung_status szstatus
											JOIN extension.tbl_internat_massnahme_zuordnung szuordnung USING (massnahme_zuordnung_id)
										WHERE szuordnung.massnahme_zuordnung_id = zstatus.massnahme_zuordnung_id
										ORDER BY szstatus.massnahme_zuordnung_status_id DESC LIMIT 1
									)
							)
						JOIN extension.tbl_internat_massnahme_status status USING (massnahme_status_kurzbz)
						JOIN public.tbl_studiensemester USING (studiensemester_kurzbz)
					WHERE student.student_uid = ?
					ORDER BY CASE
						WHEN status.massnahme_status_kurzbz = \'planned\' THEN 1
						WHEN status.massnahme_status_kurzbz = \'accepted\' THEN 2
						WHEN status.massnahme_status_kurzbz = \'performed\' THEN 3
						WHEN status.massnahme_status_kurzbz = \'confirmed\' THEN 4
						WHEN status.massnahme_status_kurzbz = \'declined\' THEN 5
					END, massnahme.bezeichnung_mehrsprachig['.$language.']';

		return $this->execReadOnlyQuery($query, array($student));
	}

	public function enoughECTs($student)
	{
		$query = 'SELECT
			1
			FROM extension.tbl_internat_massnahme_zuordnung zuordnung 
				JOIN extension.tbl_internat_massnahme massnahme ON zuordnung.massnahme_id = massnahme.massnahme_id
				JOIN extension.tbl_internat_massnahme_zuordnung_status zstatus ON zuordnung.massnahme_zuordnung_id = zstatus.massnahme_zuordnung_id
				JOIN tbl_prestudent ON zuordnung.prestudent_id = tbl_prestudent.prestudent_id
				JOIN tbl_student ON tbl_prestudent.prestudent_id = tbl_student.prestudent_id
			WHERE zstatus.massnahme_status_kurzbz = ?
			  AND tbl_student.student_uid = ?
			  AND zstatus.massnahme_zuordnung_status_id = (
				SELECT MAX(sub_zstatus.massnahme_zuordnung_status_id)
				FROM extension.tbl_internat_massnahme_zuordnung_status sub_zstatus
				WHERE sub_zstatus.massnahme_zuordnung_id = zuordnung.massnahme_zuordnung_id
			)
			GROUP BY tbl_student.student_uid, tbl_prestudent.prestudent_id, tbl_prestudent.studiengang_kz
			HAVING SUM(massnahme.ects) >= ?';

		return $this->execReadOnlyQuery($query, array("confirmed", $student, 5));

	}
}
