const BASE_URL = FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;
const CONTROLLER_URL = BASE_URL + "/"+CALLED_PATH;

const TABLE = '[tableuniqueid = leitungMassnahmeOverview] #tableWidgetTabulator';

var STUDIENSEMESTER;

function form_status(cell, formatterParams)
{
	var massnahme = cell.getData().massnahme_zuordnung_id;
	var status = cell.getData().massnahme_status_kurzbz;
	console.log(status);
	if (status !== 'confirmed' && status !== 'declined')
	{
		var div = $("<div></div>");

		if (status === 'planned')
		{
			div.append(Studiengangsleitung._addButton(massnahme,'fa-calendar-check-o', 'accepted'));
		}

		div.append('&nbsp;');
		if (status === 'performed')
			div.append(Studiengangsleitung._addButton(massnahme,'fa-calendar-minus-o', 'declined', 'entakzeptierenConfirm'));
		else
			div.append(Studiengangsleitung._addButton(massnahme,'fa-calendar-minus-o', 'declined'));
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

		var downloadNachweis = $("<button class='btn btn-default'>" +
									"<i class='fa fa-download fa-1x' aria-hidden = 'true' ></i >" +
								"</button>");

		downloadNachweis.on('click', function() {
			window.location.href = CONTROLLER_URL + '/download?massnahme=' + massnahme;
		});

		div.append(downloadNachweis);

		if (status === 'performed')
		{
			div.append('&nbsp;');
			div.append(Studiengangsleitung._addButton(massnahme,'fa-check', 'confirmed'));

			div.append('&nbsp;');
			div.append(Studiengangsleitung._addButton(massnahme,'fa-trash', 'accepted', 'fileLoeschenConfirm'));
		}
		else if (status === 'confirmed')
		{
			div.append('&nbsp;');
			div.append(Studiengangsleitung._addButton(massnahme,'fa-ban', 'accepted', 'entbestaetigenConfirm'));
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

function func_height(table){
	return $(window).height() * 0.80;
}

function resortTable(row)
{
	var table = row.getTable();
	table.setSort([
		{column: 'studiengang', dir: 'desc'},
		{column: 'student_uid', dir: 'asc'},
		{column: 'bezeichnung', dir: 'desc'}
	]);
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
		FHC_AjaxClient.ajaxCallGet(
			CALLED_PATH + "/getAktStudiensemester",
			{},
			{
				successCallback: function (data, textStatus, jqXHR) {
					if (FHC_AjaxClient.isError(data))
					{
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data))
					}

					if (FHC_AjaxClient.isSuccess(data))
					{
						data = FHC_AjaxClient.getData(data);
						STUDIENSEMESTER = data;
					}
				},
				errorCallback: function (jqXHR, textStatus, errorThrown) {
					FHC_AjaxClient.hideVeil();
					FHC_DialogLib.alertError(FHC_AjaxClient.getError(jqXHR));
				}
			}
		)
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
							'massnahme_akzeptieren': data.status
						});
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

	_lastSemester: function(data, filterParams)
	{
		return data.max_semester === data.ausbildungssemester && data.student_studiensemester === STUDIENSEMESTER;
	},

	_addButton: function(massnahme, icon, status, confirmText = null)
	{
		var button = $("<button class='btn btn-default'>" +
							"<i class='fa "+ icon +" fa-1x' aria-hidden = 'true'></i >" +
						"</button>");


		$(button).on('click', function() {
			var data = {
				'massnahme_id' : massnahme,
				'status': status
			};

			if (confirmText !== null)
			{
				if (confirm(FHC_PhrasesLib.t('international', confirmText)))
				{
					Studiengangsleitung.setStatus(data);
				}
			}
			else
				Studiengangsleitung.setStatus(data);
		});
		return button;
	},
}

$(document).ready(function() {

	Studiengangsleitung.getAktStudiensemester();

	$("#showOpen").click(function() {
		$(TABLE).tabulator('setFilter',
			[
				{field: 'massnahme_status_kurzbz', type: '=', value: 'planned'}
			]
		);
	});

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
		$(TABLE).tabulator('setFilter', Studiengangsleitung._lastSemester);
	});

	$('#deleteFilter').click(function() {
		$(TABLE).tabulator('clearFilter');
	});
});
