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
					WHERE get_rolle_prestudent(tbl_prestudent.prestudent_id, NULL) = ? AND tbl_prestudent.studiengang_kz IN ? AND tbl_student.prestudent_id NOT IN (
						SELECT DISTINCT(tbl_internat_massnahme_zuordnung.prestudent_id)
						FROM extension.tbl_internat_massnahme_zuordnung
					)
					';


		return $this->execQuery($query, array($stgs, 'declined', 'Student', $stgs));
	}
	public function getDataStudiengangsleitung($stgs)
	{
		$language = getUserLanguage() == 'German' ? 1 : 2;
		$query = 'SELECT zuordnung.massnahme_zuordnung_id,
			student.student_uid,
			studiengang.kurzbzlang AS "studiengang_kurz",
			studiengang.studiengang_kz,
			person.vorname,
			person.nachname,
			massnahme.bezeichnung_mehrsprachig['. $language .'] AS "bezeichnung",
			status.bezeichnung_mehrsprachig['.$language.'] AS "status_bezeichnung",
			status.massnahme_status_kurzbz,
			zuordnung.anmerkung,
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
			(
				SELECT EXISTS (
					SELECT 1
					FROM campus.tbl_lvgesamtnote
					JOIN lehre.tbl_lehrveranstaltung ON tbl_lvgesamtnote.lehrveranstaltung_id = tbl_lehrveranstaltung.lehrveranstaltung_id
					JOIN public.tbl_student ON tbl_lvgesamtnote.student_uid = tbl_student.student_uid
					WHERE tbl_student.student_uid = student.student_uid
						AND tbl_lehrveranstaltung.bezeichnung = ?
				)
			) AS "note"
			FROM
		tbl_studentlehrverband
		JOIN  tbl_student student ON tbl_studentlehrverband.student_uid = student.student_uid
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
			WHERE get_rolle_prestudent(prestudent.prestudent_id, NULL) = ?
			AND tbl_studentlehrverband.studiengang_kz IN ?
			GROUP BY  student.student_uid,
				person.person_id,
				zuordnung.massnahme_zuordnung_id,
				massnahme.massnahme_id,
				status.massnahme_status_kurzbz,
				studiengang.studiengang_kz
			ORDER BY studiengang.kurzbzlang,
				student_uid ASC,
			CASE
				WHEN status.massnahme_status_kurzbz = \'planned\' THEN 1
				WHEN status.massnahme_status_kurzbz = \'accepted\' THEN 2
				WHEN status.massnahme_status_kurzbz = \'performed\' THEN 3
				WHEN status.massnahme_status_kurzbz = \'confirmed\' THEN 4
				WHEN status.massnahme_status_kurzbz = \'declined\' THEN 5
			END';
		return $this->execReadOnlyQuery($query, array('International Skills', 'Student', $stgs));
	}
	public function getDataStudiengangsleitungBenotung($stg, $stsem)
	{
		$query = 'SELECT student_uid,
					(
						SELECT EXISTS (
							SELECT 1
							FROM campus.tbl_lvgesamtnote
							JOIN lehre.tbl_lehrveranstaltung ON tbl_lvgesamtnote.lehrveranstaltung_id = tbl_lehrveranstaltung.lehrveranstaltung_id
							WHERE tbl_lvgesamtnote.student_uid = subquery.student_uid
								AND studiensemester_kurzbz = ?
								AND tbl_lehrveranstaltung.bezeichnung = ?
						)
					) AS note
				FROM
					(
						SELECT
							student.student_uid,
							ects
						FROM
							 extension.tbl_internat_massnahme_zuordnung zuordnung
								JOIN extension.tbl_internat_massnahme massnahme USING (massnahme_id)
								JOIN extension.tbl_internat_massnahme_zuordnung_status zstatus ON (zuordnung.massnahme_zuordnung_id = zstatus.massnahme_zuordnung_id AND zstatus.massnahme_zuordnung_status_id = (SELECT massnahme_zuordnung_status_id FROM extension.tbl_internat_massnahme_zuordnung_status sstatus JOIN extension.tbl_internat_massnahme_zuordnung szuordnung USING (massnahme_zuordnung_id) WHERE szuordnung.massnahme_zuordnung_id = zstatus.massnahme_zuordnung_id ORDER BY massnahme_zuordnung_status_id DESC LIMIT 1))
								JOIN extension.tbl_internat_massnahme_status status USING (massnahme_status_kurzbz)
								JOIN tbl_prestudent prestudent ON zuordnung.prestudent_id = prestudent.prestudent_id
								JOIN tbl_prestudentstatus prestudentstatus ON prestudent.prestudent_id = prestudentstatus.prestudent_id
								JOIN tbl_student student ON prestudent.prestudent_id = student.prestudent_id
								JOIN tbl_studentlehrverband ON tbl_studentlehrverband.student_uid = student.student_uid
								JOIN tbl_person person ON prestudent.person_id = person.person_id
								JOIN tbl_studiengang studiengang ON prestudent.studiengang_kz = studiengang.studiengang_kz
						WHERE
							get_rolle_prestudent(prestudent.prestudent_id, NULL) = ?
							AND massnahme_status_kurzbz = ?
							AND prestudent.studiengang_kz = ?
							AND prestudentstatus.studiensemester_kurzbz = ?
						GROUP BY student.student_uid, massnahme_status_kurzbz, massnahme_id, ects
					) subquery
					JOIN campus.vw_student ON vw_student.uid = student_uid
					JOIN tbl_studiengang ON vw_student.studiengang_kz = tbl_studiengang.studiengang_kz
					WHERE semester = (
					    SELECT max_semester
					    FROM tbl_studiengang
					    WHERE tbl_studiengang.studiengang_kz = vw_student.studiengang_kz
					)
				GROUP BY student_uid,
						person_id,
						tbl_studiengang.typ,
						tbl_studiengang.kurzbz,
						vorname, nachname
				HAVING SUM(ects) >= ?';
		return $this->execReadOnlyQuery($query, array($stsem, 'International Skills', 'Student', 'confirmed', $stg, $stsem, 5));
	}
	
	public function getDataStudent($student, $language)
	{
		$query = 'SELECT
					massnahme.bezeichnung_mehrsprachig['.$language.'] AS "bezeichnung",
					massnahme.ects,
					zuordnung.studiensemester_kurzbz,
					status.bezeichnung_mehrsprachig['.$language.'] AS "status",
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
					END';

		return $this->execReadOnlyQuery($query, array($student));
	}
}
