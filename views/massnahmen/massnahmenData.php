<?php
$language = getUserLanguage() === 'German' ? '1' : '2';

$query = 'SELECT massnahme_id AS "MassnahmeID",
				bezeichnung_mehrsprachig['. $language .'] AS "Bezeichnung",
				beschreibung_mehrsprachig['. $language .'] AS "Beschreibung",
				ects as "ECTS",
				aktiv as "Aktiv"
			FROM extension.tbl_internat_massnahme
			ORDER BY aktiv DESC, ects DESC, bezeichnung_mehrsprachig['. $language .']
			';

$filterWidgetArray = array(
	'query' => $query,
	'app' => 'international',
	'tableUniqueId' => 'massnahmeOverview',
	'datasetName' => 'massnahmenOverview',
	'filter_id' => $this->input->get('filter_id'),
	'requiredPermissions' => 'extension/internationalMassnahme:r',
	'datasetRepresentation' => 'tabulator',
	'additionalColumns' => array(
		'Details'
	),
	'columnsAliases' => array(

	),
	'datasetRepOptions' => '{
		index: "massnahme_id",
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
		selectableRangeMode: "click",
		selectablePersistence: false
	}',
	'datasetRepFieldsDefs' => '{
		MassnahmeID: {visible: false},
		Aktiv: {formatter: form_aktiv},
		Details: {formatter: form_details},
	}'
);

echo $this->widgetlib->widget('TableWidget', $filterWidgetArray);
?>
