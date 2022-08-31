<?php

$language = getUserLanguage() === 'German' ? '1' : '2';

$query = '
	SELECT zuordnung.massnahme_zuordnung_id,
			student.student_uid,
			studiengang.kurzbzlang AS "studiengang",
			person.vorname,
			person.nachname,
			massnahme.bezeichnung_mehrsprachig['. $language .'] AS "bezeichnung",
			status.bezeichnung_mehrsprachig['.$language.'] AS "status_bezeichnung",
			status.massnahme_status_kurzbz,
			zuordnung.anmerkung,
			zuordnung.studiensemester_kurzbz AS "studiensemester",
			zuordnung.dms_id AS "document",
			status.massnahme_status_kurzbz AS "akzeptieren",
			status.massnahme_status_kurzbz AS "massnahme_akzeptieren",
			massnahme.ects AS "ects",
			student.student_uid AS "kontakt",
			studiengang.max_semester as "max_semester",
			(
				SELECT sstatus.ausbildungssemester
				FROM public.tbl_prestudentstatus sstatus
				WHERE sstatus.prestudent_id = student.prestudent_id AND sstatus.status_kurzbz = \'Student\'
				ORDER BY ausbildungssemester DESC LIMIT 1
			) AS "ausbildungssemester",
			(
				SELECT sstatus.studiensemester_kurzbz
				FROM public.tbl_prestudentstatus sstatus
				WHERE sstatus.prestudent_id = student.prestudent_id AND sstatus.status_kurzbz = \'Student\'
				ORDER BY ausbildungssemester DESC LIMIT 1
			) AS "student_studiensemester"
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
		JOIN tbl_studiengang studiengang on prestudent.studiengang_kz = studiengang.studiengang_kz
		JOIN extension.tbl_internat_massnahme_status status USING (massnahme_status_kurzbz)
		JOIN public.tbl_studiensemester ON (tbl_studiensemester.studiensemester_kurzbz = zuordnung.studiensemester_kurzbz)
	WHERE oe_kurzbz IN (\''. $oeKurz .'\')';

$filterWidgetArray = array(
	'query' => $query,
	'app' => 'international',
	'tableUniqueId' => 'leitungMassnahmeOverview',
	'filter_id' => $this->input->get('filter_id'),
	'requiredPermissions' => 'extension/internationalReview:r',
	'datasetRepresentation' => 'tabulator',
	'additionalColumns' => array(
	),
	'columnsAliases' => array(
		'MassnahmeID',
		'StudentUID',
		ucfirst($this->p->t('lehre', 'studiengang')) ,
		ucfirst($this->p->t('person', 'vorname')) ,
		ucfirst($this->p->t('person', 'nachname')),
		ucfirst($this->p->t('ui', 'bezeichnung')),
		ucfirst($this->p->t('global', 'status')),
		'StatusKurz',
		ucfirst($this->p->t('global', 'anmerkung')),
		ucfirst($this->p->t('international', 'studiensemesterGeplant')),
		'Document',
		ucfirst($this->p->t('international', 'planAkzeptieren')),
		ucfirst($this->p->t('international', 'bestaetigungAkzeptieren')),
		ucfirst($this->p->t('international', 'ectsMassnahme')),
		ucfirst($this->p->t('global', 'kontakt')),
	),
	'datasetRepOptions' => '{
		index: "massnahme_zuordnung_id",
		height: func_height(this),
		layout: "fitColumns",
		persistantLayout: false,
		headerFilterPlaceholder: " ",
		tableWidgetHeader: false,
		columnVertAlign:"center",
		columnAlign:"center",
		fitColumns:true,
		selectable: false,
		groupClosedShowCalcs:true,
		groupStartOpen: [true, false],
		groupBy: ["student_uid"],
		selectableRangeMode: "click",
		selectablePersistence: false,
		initialSort:[
			{column:"studiengang", dir:"desc"},
			{column:"student_uid", dir:"asc"},
			{column:"bezeichnung", dir:"desc"}
		],
		rowUpdated:function(row){
			resortTable(row);
		},
		groupHeader:function(value, count, data, group){
			return (data[0].vorname + " " + data[0].nachname + " (" +value + ")");
		}
	}',
	'datasetRepFieldsDefs' => '{
		massnahme_zuordnung_id: {visible: false},
		student_uid: {visible: false},
		studiengang: {width: "150"},
		vorname: {visible: false, width: "250"},
		nachname: {visible: false, width: "250"},
		bezeichnung: {width: "400"},
		status_bezeichnung: {width: "100"},
		massnahme_status_kurzbz: {visible: false},
		anmerkung: {visible: true},
		studiensemester: {width: "250"},
		document: {visible: false},
		akzeptieren: {formatter: form_status, align:"center", width: "200"},
		massnahme_akzeptieren: {formatter: form_confirmation, align:"center", width: "250"},
		ects: {align:"center", bottomCalc:sumETCs, bottomCalcParams:{precision:2}, width: "200"},
		max_semester: {visible: false},
		ausbildungssemester: {visible: false},
		student_studiensemester: {visible: false},
		kontakt: {formatter: form_kontakt, align:"center", width: "150"}
	}'
);
echo $this->widgetlib->widget('TableWidget', $filterWidgetArray);
?>
