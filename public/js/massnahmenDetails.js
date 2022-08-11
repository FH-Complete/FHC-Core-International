const BASE_URL = FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;
const CONTROLLER_URL = BASE_URL + "/"+CALLED_PATH;

var MassnahmenDetails = {

	updateMassnahme: function(data)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + "/updateMassnahme",
			data,
			{
				successCallback: function(data, textStatus, jqXHR)
				{
					if (FHC_AjaxClient.isSuccess(data))
						FHC_DialogLib.alertSuccess(FHC_AjaxClient.getData(data))

					if (FHC_AjaxClient.isError(data))
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data))
				},
				errorCallback: function(jqXHR, textStatus, errorThrown)
				{
					FHC_DialogLib.alertError(FHC_AjaxClient.getError(jqXHR));
				}
			}
		)
	},
	deleteMassnahme: function(data)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + "/deleteMassnahme",
			data,
			{
				successCallback: function(data, textStatus, jqXHR)
				{
					if (FHC_AjaxClient.isError(data))
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data))

					if (FHC_AjaxClient.isSuccess(data))
						window.location = CONTROLLER_URL + '/index';
				},
				errorCallback: function(jqXHR, textStatus, errorThrown)
				{
					FHC_DialogLib.alertError(FHC_AjaxClient.getError(jqXHR));
				}
			}
		)
	},
}

$(document).ready(function() {
	$('#update').click(function()
	{
		var hiddenmassnahmenid = $('#hiddenmassnahmenid').val();
		var bezeichnung = $('#bezeichnung').val();
		var bezeichnungeng = $('#bezeichnungeng').val();
		var ects = $('#ects').val();
		var beschreibung = $('#beschreibung').val();
		var beschreibungeng = $('#beschreibungeng').val();
		var aktiv = $('#aktiv').is(":checked");

		if (hiddenmassnahmenid === '' || bezeichnung === '' || bezeichnungeng === '' || ects === '' || beschreibung === '' || beschreibungeng === '')
			return FHC_DialogLib.alertWarning('Please fill out all fields')

		var data = {
			'hiddenmassnahmenid' : hiddenmassnahmenid,
			'bezeichnung' : bezeichnung,
			'bezeichnungeng' : bezeichnungeng,
			'ects' : ects,
			'beschreibung' : beschreibung,
			'beschreibungeng' : beschreibungeng,
			'aktiv' : aktiv
		};
		MassnahmenDetails.updateMassnahme(data);
	});

	$('#loeschen').click(function()
	{
		if (!confirm("Möchten Sie die Massnahme löschen?"))
			return;

		var hiddenmassnahmenid = $('#hiddenmassnahmenid').val();

		var data = {
			'massnahmeID' : hiddenmassnahmenid
		};
		MassnahmenDetails.deleteMassnahme(data);
	});
});
