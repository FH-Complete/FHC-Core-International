const BASE_URL = FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;
const CONTROLLER_URL = BASE_URL + "/"+CALLED_PATH;

const TABLE = '[tableuniqueid = massnahmeOverview] #tableWidgetTabulator';

function form_aktiv(cell, formatterParams)
{
	var aktiv = cell.getData().Aktiv;

	if (aktiv === 'true')
		return "<i class='fa fa-check'></i>";
	else if (aktiv === 'false')
		return "<i class='fa fa-times'></i>";
}

function form_details(cell, formatterParams)
{
	var massnahme = cell.getData().MassnahmeID;
	//return  BASE_URL + "/" + APPROVE_ANRECHNUNG_DETAIL_URI + "?anrechnung_id=" + cell.getData().anrechnung_id


	var edit = $("<div>" +
		"<a style='color: inherit' href='"+ CONTROLLER_URL +"/showMassnahme?massnahme_id="+massnahme +"'>" +
		"<i class='fa fa-edit fa-1x' aria-hidden = 'true' ></i >" +
		"</a>" +
		"</div>");

	return edit[0];
}

function func_height(table){
	return $(window).height() * 0.50;
}

var Massnahmen = {

	addMassnahme: function(data)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + "/addMassnahme",
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

						$(TABLE).tabulator(
							'addRow',
							JSON.stringify({
								MassnahmeID: data.massnahme_id,
								Bezeichnung: data.bezeichnung,
								Beschreibung: data.beschreibung,
								Aktiv: data.aktiv,
								ECTs: data.ects
							})
						);
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
}

$(document).ready(function() {

	$('.massnahmenAdd').insertAfter('hr');

	$('.hinzufuegen').click(function()
	{
		$('.massnahmenAdd').slideToggle(500);
	});

	$('#speichern').click(function()
	{
		var bezeichnung = $('#bezeichnung').val();
		var bezeichnungeng = $('#bezeichnungeng').val();
		var ects = $('#ects').val();
		var beschreibung = $('#beschreibung').val();
		var beschreibungeng = $('#beschreibungeng').val();
		var aktiv = $('#aktiv').is(":checked");

		if (bezeichnung === '' || bezeichnungeng === '' || ects === '' || beschreibung === '' || beschreibungeng === '')
			return FHC_DialogLib.alertWarning('Please fill out all fields')

		var data = {
			'bezeichnung' : bezeichnung,
			'bezeichnungeng' : bezeichnungeng,
			'ects' : ects,
			'beschreibung' : beschreibung,
			'beschreibungeng' : beschreibungeng,
			'aktiv' : aktiv
		};
		Massnahmen.addMassnahme(data);
	});

});
