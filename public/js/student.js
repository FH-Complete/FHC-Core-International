const BASE_URL = FHC_JS_DATA_STORAGE_OBJECT.app_root + FHC_JS_DATA_STORAGE_OBJECT.ci_router;
const CALLED_PATH = FHC_JS_DATA_STORAGE_OBJECT.called_path;
const CONTROLLER_URL = BASE_URL + "/"+CALLED_PATH;

const TABLE = '[tableuniqueid = studentMassnahmeOverview] #tableWidgetTabulator';

function form_upload(cell, formatterParams)
{
	var massnahme = cell.getData().massnahme_zuordnung_id;
	var document = cell.getData().dms_id;
	var status = cell.getData().massnahme_status_kurzbz;

	var div = $('<div></div>');

	if (document === null && status === 'accepted')
	{
		var input = $("<label for='fileNachweis_" + massnahme + "' >" +
							"<i class='btn btn-default fa fa-upload fa-1x' aria-hidden='true'></i>" +
						"</label>" +
						"<input type='file' name='uploadfile' id='fileNachweis_"+ massnahme +"' accept='.pdf' class='fileNachweis hidden'/> ");

		input.on('change', function() {
			var fileNachweis = $('#fileNachweis_' + massnahme);

			if (fileNachweis.val() === '')
				return FHC_DialogLib.alertWarning('Please select a file');

			var file = fileNachweis.prop('files');
			var data = {
				'file' : file,
				'massnahmenZuordnung' : massnahme
			}
			Student.uploadNachweis(data);
		});

		div.append(input);

		return div[0];
	}
	else if (document !== null)
	{
		var downloadNachweis = Student._addButton('fa-download');

		downloadNachweis.on('click', function(){
			window.location.href = CONTROLLER_URL + '/studentDownloadNachweis?massnahmenZuordnung=' + massnahme;
		});

		div.append(downloadNachweis);

		if (status !== 'confirmed' && status !== 'declined')
		{
			var deleteNachweis = Student._addButton('fa-trash');

			deleteNachweis.on('click', function() {

				var data = {
					'massnahmenZuordnung' : massnahme
				}

				Student.deleteNachweis(data);
			});

			div.append('&nbsp;');
			div.append(deleteNachweis);
		}
		return div[0];
	}
	else
		return '-';
}

function form_document(cell, formatterParams)
{
	var massnahme = cell.getData().massnahme_zuordnung_id;
	var status = cell.getData().massnahme_status_kurzbz;

	if (status !== 'confirmed' && status !== 'declined')
	{
		var deleteMassnahme = Student._addButton('fa-remove');

		deleteMassnahme.on('click', function() {

			var data = {
				'massnahmenZuordnung' : massnahme
			}

			if (status === 'accepted' || status === 'performed')
			{
				if (confirm(FHC_PhrasesLib.t('international', 'massnahmeLoeschenConfirm')))
				{
					Student.deleteStudentMassnahme(data);
				}
			}
			else
				Student.deleteStudentMassnahme(data);

		});

		return deleteMassnahme[0];
	}
	else
		return '-';
}

function func_height(table){
	return $(window).height() * 0.5;
}

function func_groupHeader(group)
{
	switch (group)
	{
		case 'planned' :
			return FHC_PhrasesLib.t('international', 'geplanteMassnahmen');
		case 'accepted' :
			return FHC_PhrasesLib.t('international', 'akzpetierteMassnahmen');
		case 'performed' :
			return FHC_PhrasesLib.t('international', 'durchgefuehrteMassnahmen');
		case 'confirmed' :
			return FHC_PhrasesLib.t('international', 'bestaetigteMassnahmen');
		case 'declined' :
			return FHC_PhrasesLib.t('international', 'abgelehnteMassnahmen');
	}
}

function func_rowUpdated(row)
{
	$(TABLE).tabulator('setGroupBy', 'massnahme_status_kurzbz')
}

$(document).ready(function()
{
	$('.showInfoText').click(function()
	{
		$('.internationalskills').slideToggle(300);
	});

	$('#massnahmenSelect').change(function()
	{
		var massnahme = $('#massnahmenSelect').val();

		$('.beschreibung').hide();
		$('#beschreibung_' + massnahme).toggle(300);
	});

	$('#addStudentMassnahme').click(function()
	{
		var massnahme = $('#massnahmenSelect').val();
		var studiensemester = $('#studiensemesterSelect').val();
		var anmerkung = $('#massnahmenAnmerkung').val();

		if (massnahme === null || studiensemester === null)
		{
			return FHC_DialogLib.alertWarning(FHC_PhrasesLib.t('ui', 'errorFelderFehlen'));
		}

		var data = {
			'massnahme': massnahme,
			'studiensemester': studiensemester,
			'anmerkung' : anmerkung
		}

		Student.addStudentMassnahme(data);
	});
});


var Student = {
	deleteNachweis: function(data) {
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + "/studentDeleteNachweis",
			data,
			{
				successCallback: function (data, textStatus, jqXHR) {
					if (FHC_AjaxClient.isError(data)) {
						FHC_DialogLib.alertError(FHC_AjaxClient.getError(data))
					}

					if (FHC_AjaxClient.isSuccess(data)) {
						data = FHC_AjaxClient.getData(data);

						$(TABLE).tabulator('updateRow', data, {'dms_id' : null, massnahme_status_kurzbz: 'accepted'});
					}
				},
				errorCallback: function (jqXHR, textStatus, errorThrown) {
					FHC_AjaxClient.hideVeil();
					FHC_DialogLib.alertError(FHC_AjaxClient.getError(jqXHR));
				}
			}
		)
	},

	uploadNachweis: function(data)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + "/studentAddNachweis",
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
						//$(TABLE).updateRow(data.massnahme, {Document : data.dms_id});
						$(TABLE).tabulator('updateRow',
							data.massnahme,
							{
								'dms_id' : data.dms_id,
								massnahme_status_kurzbz: 'performed'
							}
						)
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

	deleteStudentMassnahme: function(data)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + "/studentDeleteMassnahme",
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
						$(TABLE).tabulator('deleteRow', data.massnahme_zuordnung_id);
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

	addStudentMassnahme: function(data)
	{
		FHC_AjaxClient.ajaxCallPost(
			CALLED_PATH + "/studentAddMassnahme",
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
							{
								massnahme_zuordnung_id: data.massnahme_zuordnung_id,
								bezeichnung: data.bezeichnung,
								massnahme_status_kurzbz: 'planned',
								dms_id: null,
								studiensemester_kurzbz: data.studiensemester,
								ects: data.ects,
								massnahme_id: data.massnahme_id,
								status: 'planned',
								anmerkung: data.anmerkung
							}, true
						);

						Student._resetFields();
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

	_resetFields: function()
	{
		$('#massnahmenSelect').prop("selectedIndex", 0);
		$('#studiensemesterSelect').prop("selectedIndex", 0);
		$('#massnahmenAnmerkung').val('');

		$('.beschreibung').hide();
	},

	_addButton: function(icon)
	{
		return $("<button class='btn btn-default'>" +
					"<i class='fa "+ icon +" fa-1x' aria-hidden = 'true'></i >" +
				"</button>");

	},
}