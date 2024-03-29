const BASE_URL = FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;
const CONTROLLER_URL = BASE_URL + "/"+CALLED_PATH;

const TABLE = '[tableuniqueid = leitungMassnahmeOverview] #tableWidgetTabulator';


function form_modal(cell, formatterParams)
{
	var massnahme = cell.getData().massnahme_zuordnung_id;
	return Studiengangsleitung._addModal(massnahme);
}

function form_status(cell, formatterParams)
{
	var massnahme = cell.getData().massnahme_zuordnung_id;
	var status = cell.getData().massnahme_status_kurzbz;

	if (status !== 'confirmed' && status !== 'declined')
	{
		var div = $("<div></div>");

		if (status === 'planned')
		{
			div.append(Studiengangsleitung._addButton(massnahme,'fa-calendar-check-o', 'accepted', 'planAkzeptieren'));
		}

		div.append('&nbsp;');
		div.append(Studiengangsleitung._addButton(massnahme,'fa-calendar-minus-o', 'declined', 'planAblehnen',true));

		return div[0];
	}
	else
		return '-';
}

function form_confirmation(cell, formatterParams)
{
	var massnahme = cell.getData().massnahme_zuordnung_id;
	var status = cell.getData().massnahme_status_kurzbz;
	var document = cell.getData().document;

	if (document !== null)
	{
		var div = $("<div></div>");

		var downloadNachweis = $("<button class='btn btn-default' title='" + FHC_PhrasesLib.t("international", "ectsBestaetigt") +"'>" +
									"<i class='fa fa-download fa-1x' aria-hidden = 'true' ></i >" +
								"</button>");

		downloadNachweis.on('click', function() {
			window.location.href = CONTROLLER_URL + '/download?massnahme=' + massnahme;
		});

		div.append(downloadNachweis);

		if (status === 'performed')
		{
			div.append('&nbsp;');
			div.append(Studiengangsleitung._addButton(massnahme,'fa-check', 'confirmed', 'bestaetigungAkzeptieren'));

			div.append('&nbsp;');
			div.append(Studiengangsleitung._addButton(massnahme,'fa-remove', 'declined', 'bestaetigungAblehnen', true));

		}
		else if (status === 'confirmed')
		{
			div.append('&nbsp;');
			div.append(Studiengangsleitung._addButton(massnahme,'fa-ban', 'performed', 'entbestaetigenConfirm'));
		}

		return div[0];
	}
	else
		return '-';
}

function form_kontakt(cell, formatterParams)
{
	return "<a href='mailto:"+ cell.getData().student_uid +"@technikum-wien.at?subject=" + cell.getData().bezeichnung + "'><i class='fa fa-envelope'></i></a>"
}

function func_height(table)
{
	return $(window).height() * 0.70;
}

function func_selectableCheck(row)
{
	return row.getData().massnahme_status_kurzbz === 'planned';
}

function resortTable(row)
{
	var table = row.getTable();
	table.setSort([]);
}

function sumETCs(values, data, calcParams)
{
	var sum = 0;

	data.forEach(function(value)
	{
		if (value.massnahme_status_kurzbz === 'confirmed')
		{
			sum += parseInt(value.ects);
		}
	});

	return FHC_PhrasesLib.t("international", "ectsBestaetigt") + " " + sum  + ".00";
}

var Studiengangsleitung = {

	getAktStudiensemester: function()
	{
		return new Promise(function(resolve, reject) {
			FHC_AjaxClient.ajaxCallGet(
				CALLED_PATH + "/getAktStudiensemester",
				{},
				{
					successCallback: function (data, textStatus, jqXHR) {
						if (FHC_AjaxClient.isError(data)) {
							FHC_DialogLib.alertError(FHC_AjaxClient.getError(data))
						}
						if (FHC_AjaxClient.isSuccess(data)) {
							resolve(FHC_AjaxClient.getData(data));
						}
					},
					errorCallback: function (jqXHR, textStatus, errorThrown) {
						FHC_AjaxClient.hideVeil();
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(jqXHR));
					}
				}
			)
		})
	},

	setStatus: function(data)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + "/setStatus",
			data,
			{
				successCallback: function(data, textStatus, jqXHR)
				{
					if (FHC_AjaxClient.isError(data))
					{
						FHC_AjaxClient.hideVeil();
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data))
					}

					if (FHC_AjaxClient.isSuccess(data))
					{
						data = FHC_AjaxClient.getData(data);
						$(TABLE).tabulator('updateRow', data.massnahme,
						{
							'massnahme_status_kurzbz' : data.status,
							'document' : data.dms_id,
							'status_bezeichnung' : data.status_bezeichnung,
							'akzeptieren': data.status,
							'massnahme_akzeptieren': data.status,
							'anmerkung_stgl' : data.anmerkung_stgl
						}).then(() => {
							$(TABLE).tabulator('scrollToRow', data.massnahme, 'top', true)
						});
						$('#absageModal_' + data.massnahme).modal('hide');
						$(TABLE).tabulator('deselectRow', data.massnahme);
					}
				},
				errorCallback: function(jqXHR, textStatus, errorThrown)
				{
					FHC_AjaxClient.hideVeil();
					FHC_DialogLib.alertError(FHC_AjaxClient.getError(jqXHR));
				}
			}
		)
	},

	getStudents: function(filterData)
	{
		FHC_AjaxClient.ajaxCallGet(
			CALLED_PATH + "/getStudents",
				filterData,
			{
				successCallback: function (data, textStatus, jqXHR) {
					if (FHC_AjaxClient.isSuccess(data))
					{
						if (FHC_AjaxClient.isError(data))
						{
							FHC_DialogLib.alertError(FHC_AjaxClient.getError(data))
						}

						if (FHC_AjaxClient.hasData(data))
						{
							var studentsData = FHC_AjaxClient.getData(data);
							Studiengangsleitung._setFilter(studentsData);
						}
						else
							Studiengangsleitung._setFilter([]);
					}
				},
				errorCallback: function (jqXHR, textStatus, errorThrown) {
					FHC_AjaxClient.hideVeil();
					FHC_DialogLib.alertError(FHC_AjaxClient.getError(jqXHR));
				}
			}
		)
	},

	_setFilter: function(filterData)
	{
		$(TABLE).tabulator('setFilter',
			[
				{field: 'student_uid', type: 'in', value: filterData}
			]
		);
	},

	_getMassnahmeIdFromElementId(elementid)
	{
		return elementid.substr(elementid.indexOf("_") + 1);
	},

	_addButton: function(massnahme, icon, status, title, absage = false)
	{
		var button = $("<button class='btn btn-default' title='" + FHC_PhrasesLib.t("international", title) + "'>" +
							"<i class='fa "+ icon +" fa-1x' aria-hidden = 'true'></i >" +
						"</button>");

		$(button).on('click', function() {
			var data = {
				'massnahme_id' : massnahme,
				'status': status
			};

			if (absage)
			{
				let absageGrund = '';

				$('#absageModalLabelTitel_' +massnahme).text(FHC_PhrasesLib.t("international", title));
				$('#absageModal_' + massnahme).appendTo('body').modal('show');
				$('#saveAbsageGrund_' + massnahme).unbind().click(function() {

					absageGrund = $('#inputAbsageGrundText_' + massnahme).val();

					data.absagegrund = absageGrund;

					Studiengangsleitung.setStatus(data);
				});
			}
			else
			{
				Studiengangsleitung.setStatus(data);
			}
		});
		return button;
	},

	_addModal: function(massnahme)
	{
		return "<div class='modal fade absageModal'  id='absageModal_" + massnahme + "' tabindex='0' role='dialog' aria-labelledby='absageModalLabel' aria-hidden='true'>" +
					"<div class='modal-dialog'>" +
						"<div class='modal-content'>" +
							"<div class='modal-header'>" +
								"<button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times; </button>" +
								"<h4 class='modal-title' id='absageModalLabelTitel_" + massnahme +"'></span></h4>" +
							"</div>" +
							"<div class='modal-body'>" +
								"<div class='form-group'>" +
									"<label for='inputAbsageGrundText_" + massnahme +"'>"+ FHC_PhrasesLib.t('international', 'grund') +"</label>" +
									"<textarea id='inputAbsageGrundText_" + massnahme +"' required  rows='5' class='form-control'></textarea>" +
								"</div>" +
							"</div>" +
							"<div class='modal-footer'>" +
								"<button type='button' class='btn btn-default saveAbsageGrund' id='saveAbsageGrund_" + massnahme +"'>Speichern</button>" +
								"<button type='button' class='btn btn-default' data-dismiss='modal'>Abbrechen</button>" +
							"</div>" +
						"</div>" +
					"</div>" +
				"</div>";
	},
}

$(document).ready(function() {

	Studiengangsleitung.getAktStudiensemester().then(function(STUDIENSEMESTER)
	{
		$('#currentSemester').click(function() {
			$(TABLE).tabulator('setFilter',
				[
					{field: 'student_studiensemester', type: '=', value: STUDIENSEMESTER}
				]
			);
		});

		$('#currentOpenSemester').click(function() {
			$(TABLE).tabulator('setFilter',
				[
					{field: 'studiensemester', type: '=', value: STUDIENSEMESTER}
				]
			);
		});

		$('#lastSemester').click(function() {
			$(TABLE).tabulator('setFilter', (function (data) {
				return data.max_semester === data.ausbildungssemester && data.student_studiensemester === STUDIENSEMESTER;
			}));
		});
	});
	$('#acceptAll').click(function() {
		var rows = $(TABLE).tabulator('getSelectedRows');

		rows.forEach(function(row) {
			if (row.getData().massnahme_status_kurzbz === 'planned')
			{
				var data = {
					'massnahme_id' : row.getData().massnahme_zuordnung_id,
					'status': 'accepted'
				};

				Studiengangsleitung.setStatus(data);
			}
		});
	});

	$('#selectAll').click(function() {
		$(TABLE).tabulator('getRows').filter(row => row.getData().massnahme_status_kurzbz === 'planned')
			.forEach(row => row.select());
	});

	$('#plannedMore').click(function() {
		var data = {
			'status': ['planned', 'confirmed', 'performed', 'accepted'],
			'more': true,
			'ects': 5,
			'exists': true
		}

		Studiengangsleitung.getStudents(data);
	});

	$('#plannedLess').click(function() {
		var data = {
			'status': ['planned', 'confirmed', 'performed', 'accepted'],
			'more': false,
			'ects': 5,
			'exists': true,
		}

		Studiengangsleitung.getStudents(data);
	});

	$('#confirmedMore').click(function() {
		var data = {
			'status': ['confirmed'],
			'more': true,
			'ects': 5,
			'exists': true
		}

		Studiengangsleitung.getStudents(data);
	});

	$('#confirmedLess').click(function() {
		var data = {
			'status': ['confirmed'],
			'more': false,
			'ects': 5,
			'exists': false
		}

		Studiengangsleitung.getStudents(data);
	});

	$("#showOpen").click(function() {
		$(TABLE).tabulator('setFilter',
			[
				{field: 'massnahme_status_kurzbz', type: '=', value: 'planned'}
			]
		);
	});

	$("#showUploaded").click(function() {
		$(TABLE).tabulator('setFilter',
			[
				{field: 'massnahme_status_kurzbz', type: '=', value: 'performed'}
			]
		);
	});

	$('#deleteFilter').click(function() {
		$(TABLE).tabulator('clearFilter');
	});
});
