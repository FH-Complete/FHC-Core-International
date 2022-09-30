<?php
$language = getUserLanguage() === 'German' ? '1' : '2';
$student = getAuthUID();

$query = '
	SELECT 
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
	WHERE student.student_uid = \''.$student .'\'
	ORDER BY CASE
		WHEN status.massnahme_status_kurzbz = \'planned\' THEN 1
		WHEN status.massnahme_status_kurzbz = \'accepted\' THEN 2
		WHEN status.massnahme_status_kurzbz = \'performed\' THEN 3
		WHEN status.massnahme_status_kurzbz = \'confirmed\' THEN 4
		WHEN status.massnahme_status_kurzbz = \'declined\' THEN 5
	END'
;
;

$filterWidgetArray = array(
	'query' => $query,
	'app' => 'international',
	'tableUniqueId' => 'studentMassnahmeOverview',
	'datasetName' => 'studentOverview',
	'filter_id' => $this->input->get('filter_id'),
	'requiredPermissions' => 'extension/internationalStudent:r',
	'datasetRepresentation' => 'tabulator',
	'additionalColumns' => array(
	),
	'columnsAliases' => array(
		ucfirst($this->p->t('international', 'meinMassnahmeplan')),
		ucfirst($this->p->t('lehre', 'ects')),
		ucfirst($this->p->t('international', 'studiensemesterGeplant')),
		ucfirst($this->p->t('global', 'status')),
		'StatusKurz',
		ucfirst($this->p->t('global', 'anmerkung')),
		ucfirst($this->p->t('international', 'anmerkungstgl')),
		ucfirst($this->p->t('international', 'bestaetigungHochladen')),
		'MassnahmenZuordnung',
		'MassnahmenID',
		ucfirst($this->p->t('international', 'massnahmeLoeschen'))
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
		groupBy: ["massnahme_status_kurzbz"],
		groupValues: [
			["planned", "accepted", "performed", "confirmed", "declined"],
		],
		selectableRangeMode: "click",
		selectablePersistence: false,
		rowUpdated:function(row){
			func_rowUpdated(row);
		},
		groupHeader:function(value, count, data, group){
			return func_groupHeader(value);
		},
	}',
	'datasetRepFieldsDefs' => '{
		bezeichnung: {width:"200"},
		ects: {align:"right", bottomCalc:"sum", bottomCalcParams:{precision:2}, width:"100"},
		studiensemester_kurzbz: {width:"300"},
		status: {visible: false},
		massnahme_status_kurzbz: {visible:false},
		anmerkung: {visible: true},
		anmerkung_stgl: {visible: true},
		dms_id: {formatter: form_upload, align:"center",  width:"200"},
		massnahme_zuordnung_id: {visible: false},
		massnahme_id: {visible: false},
		massnahmen_status: {formatter: form_document, align: "center"}

	}'
);
echo $this->widgetlib->widget('TableWidget', $filterWidgetArray);
?>
