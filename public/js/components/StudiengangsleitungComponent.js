import {CoreFilterCmpt} from '../../../../js/components/filter/Filter.js';
import {CoreRESTClient} from '../../../../js/RESTClient.js';
import CoreBaseLayout from '../../../../js/components/layout/BaseLayout.js';
import BsModal from '../../../../js/components/Bootstrap/Modal.js';
import FormInput from "../../../../js/components/Form/Input.js";
import FhcLoader from '../../../../js/components/Loader.js';

export default {
	name: 'Studiengangsleitung',

	props: {
		stgs : {
			type: Array,
			required: true
		},
		readonly : {
			type: Boolean,
			required: true
		},
		stsems : {
			type: Array,
			required: true
		},
		aktstsem : {
			type: String,
			required: true
		},
	},
	components: {
		CoreFilterCmpt,
		CoreBaseLayout,
		BsModal,
		FormInput,
		FhcLoader
	},
	watch: {
		selectedStg(newVal) {
			this.updateTabulatorFilter();
			this.closeNotenUebernahme()
		},
		getSem(newVal) {
			this.openNotenUebernahme();
		},
		selectedOrgform(newVal, oldValue) {
			if (newVal === "")
				this._removeOrgFilter(oldValue)
			else
				this._setNotenFilter(this.filteredUids, newVal);

			this.filterLVsDropdown();
		},

	},
	data: function() {
		return {
			modalTitle: '',
			selectableStatus: 'planned',
			selectedStg: '',
			selectedStsem: '',
			selectedLv: '',
			filteredLvs: [],
			notenUebernahme: false,
			formData: {
				absageGrund: '',
				massnahme_id: '',
				status: '',
				absage: false,
				title: '',
			},
			phrasesLoaded: null,
			groupsCollapsed: false,
			sideMenuEntries: {},
			selectedOrgform: "",
			orgformen: "",
			filteredUids: [],
			tabulatorEventHandler: [
				{
					event: "rowClick",
					handler: (e, row) => {
						if(row.getData().massnahme_status_kurzbz !== this.selectableStatus) {
							e.stopPropagation();
							row.deselect();
						}
					}
				}
			],
			changedData: []
		}
	},
	async created() {
		await this.$p.loadCategory(['global', 'lehre', 'person', 'ui', 'international']).then(() => {
			this.phrasesLoaded = true;
		});
	},

	computed: {
		tabulatorOptions() {
			return {
				index: 'massnahme_zuordnung_id',
				ajaxURL: CoreRESTClient._generateRouterURI('/extensions/FHC-Core-International/Studiengangsleitung/load'),
				ajaxParams: () => {
					return {stg: this.selectedStg};
				},
				ajaxResponse: (url, params, response)=>  {
					if (CoreRESTClient.isSuccess(response))
					{
						if (CoreRESTClient.hasData(response))
							return CoreRESTClient.getData(response).retval;
						else
							return [];
					}
				},
				height: '65vh',
				maxHeight: "100%",
				layout: 'fitDataStretch',
				selectable: true,
				placeholder: "Keine Daten verfügbar",
				groupBy: ["student_uid"],
				groupClosedShowCalcs:true,
				groupStartOpen: [true],
				selectableRangeMode: "click",
				selectablePersistence: false,
				initialFilter: {},
				selectableCheck: (row) => {
					return row.getData().massnahme_status_kurzbz === this.selectableStatus;
				},
				groupHeader: function(value, count, data, group)
				{
					let sum = 0;
					data.forEach(function(item) {
						if (item.massnahme_status_kurzbz === 'confirmed')
						{
							sum += parseInt(item.ects);
						}
					});

					let color = '';
					if (data.some(item => item.massnahme_status_kurzbz === null) || data.some(item => item.massnahme_status_kurzbz === "declined"))
						color = 'red';
					if (data.some(item => item.massnahme_status_kurzbz === "confirmed") && sum < 5)
						color = 'greenyellow';
					if (data.some(item => item.massnahme_status_kurzbz === "accepted"))
						color = "yellow";
					if (data.some(item => item.massnahme_status_kurzbz === "planned") || data.some(item => item.massnahme_status_kurzbz === "performed"))
						color = 'orange';
					if (data.some(item => item.massnahme_status_kurzbz === "confirmed") && sum >= 5)
						color = "green";

					let outerDiv = document.createElement('div');
					outerDiv.style.display = 'inline-flex';
					outerDiv.style.alignItems = 'center';

					let innerDiv = document.createElement('div');
					innerDiv.classList.add(color);
					innerDiv.style.width = '50px';
					innerDiv.style.height = '20px';
					innerDiv.style.marginRight = '10px';
					innerDiv.style.display = 'inline-flex';

					outerDiv.appendChild(innerDiv);
					let textContent = document.createTextNode(data[0].vorname + " " + data[0].nachname + " (" + value + ") ");
					outerDiv.appendChild(textContent);

					return outerDiv;
				},

				columns: [
					{title: this.$p.t('lehre', 'studiengang'), field: 'studiengang_kurz', headerFilter: true},
					{title: this.$p.t('lehre', 'organisationsform'), field: 'orgform', headerFilter: true},
					{title:  this.$p.t('person', 'uid'), field: 'student_uid', headerFilter: true, width: 150},
					{title: this.$p.t('person', 'vorname'), field: 'vorname', headerFilter: true, width: 120},
					{title: this.$p.t('person', 'nachname'), field: 'nachname', headerFilter: true, width: 120},
					{title: this.$p.t('international', 'studentstatus'), field: 'status_kurzbz', headerFilter: true, width: 120},
					{title: this.$p.t('lehre', 'note'), field: 'note',  width: 50,
						formatter: "tickCross",
						headerFilter: "tickCross",
						headerFilterParams: {"tristate": true},
						hozAlign: "center",
						formatterParams: {
							tickElement: '<i class="fa fa-check text-success"></i>',
							crossElement: '<i class="fa fa-xmark text-danger"></i>'
						},
						visible:false
					},
					{title: this.$p.t('ui', 'bezeichnung'), field: 'bezeichnung', headerFilter: true, width: 400, tooltip: (e, cell) => {

							let div = document.createElement('div');
							div.style.whiteSpace = 'pre-wrap';
							div.innerHTML = cell.getData().beschreibung
							return div
						}
					},
					{title: this.$p.t('global', 'status'), field: 'status_bezeichnung', headerFilter: true, width: 100},
					{title: this.$p.t('global', 'anmerkung'), field: 'anmerkung', headerFilter: true},
					{title: this.$p.t('international', 'anmerkungstgl'), field: 'anmerkung_stgl', headerFilter: true, width: 220},
					{title: this.$p.t('lehre', 'semester'), field: 'semester', headerFilter: true, width: 100},
					{title: this.$p.t('international', 'studiensemesterGeplant'), field: 'studiensemester', headerFilter: true, width: 220},
					{
						title: this.$p.t('international', 'planAkzeptieren'),
						field: 'akzeptieren',
						headerFilter: true,
						tooltip: false,
						width: 150,
						formatter: (cell, formatterParams, onRendered) =>
						{
							var massnahme = cell.getData().massnahme_zuordnung_id;
							var status = cell.getData().massnahme_status_kurzbz;

							if (status !== 'confirmed' && status !== 'declined' && this.readonly === false && status !== 'performed')
							{
								var div = document.createElement('div');

								if (status === 'planned')
								{
									div.append(this._addButton(massnahme, 'fa-solid fa-calendar-check', 'accepted', 'planAkzeptieren'));
								}

								if (massnahme !== null)
									div.append(this._addButton(massnahme, 'fa-solid fa-calendar-minus', 'declined', 'planAblehnen', true));

								return div;
							}
							else
								return '-';
						},
					},
					{
						title: this.$p.t('international', 'bestaetigungAkzeptieren'),
						field: 'massnahme_akzeptieren',
						headerFilter: true,
						tooltip: false,
						width: 210,
						formatter: (cell, formatterParams, onRendered) =>
						{
							var massnahme = cell.getData().massnahme_zuordnung_id;
							var status = cell.getData().massnahme_status_kurzbz;
							var doc = cell.getData().document;

							if (doc !== null)
							{
								let div = document.createElement('div');
								let downloadNachweis = document.createElement('button');
								downloadNachweis.title = this.$p.t("international", "downloadBestaetigung");
								downloadNachweis.className = 'btn';
								downloadNachweis.innerHTML = "<i class='fa fa-download fa-1x' aria-hidden = 'true' ></i >";
								downloadNachweis.addEventListener('click', () => window.location.href = CoreRESTClient._generateRouterURI('extensions/FHC-Core-International/Studiengangsleitung/download?massnahme=' + massnahme));

								div.append(downloadNachweis);

								if (this.readonly === false)
								{
									if (status === 'performed')
									{
										div.append(this._addButton(massnahme, 'fa-solid fa-check', 'confirmed', 'bestaetigungAkzeptieren'));
										div.append(this._addButton(massnahme, 'fa-solid fa-xmark', 'declined', 'bestaetigungAblehnen', true));
									}
									else if (status === 'confirmed')
									{
										div.append(this._addButton(massnahme, 'fa-solid fa-ban', 'performed', 'entbestaetigenConfirm'));
									}
								}
								return div;
							}
							else
								return '-';
						}
					},
					{
						title: this.$p.t('international', 'ectsMassnahme'),
						field: 'ects',
						headerFilter: true,
						tooltip: false,
						width: 200,
						bottomCalcFormatter: (cell, calcValue) => {
							return this.$p.t('international', 'ectsBestaetigt') + " " + cell.getValue() + ".00";
						},
						bottomCalc: (values, data, calcParams) =>
						{
							var sum = 0;

							data.forEach(function(value)
							{
								if (value.massnahme_status_kurzbz === 'confirmed')
								{
									sum += parseInt(value.ects);
								}
							});
							return sum;
						}
					},
					{title: this.$p.t('lehre', 'studiensemester'), field: 'student_studiensemester', headerFilter: true},
					{
						title: this.$p.t('global', 'kontakt'),
						field: 'kontakt',
						headerFilter: true,
						tooltip: false,
						width: 100,
						formatter: (cell, formatterParams, onRendered) =>
						{
							return "<a href='mailto:"+ cell.getData().student_uid +"@technikum-wien.at?subject=" + cell.getData().bezeichnung + "'><i class='fa-solid fa-envelope'></i></a>"
						}
					},
				],
				persistenceID: "02.10.2024",
			}
		},
		getSem: {
			get() {
				return this.selectedStsem !== "" ? this.selectedStsem : this.aktstsem;
			},
			set(value) {
				this.selectedStsem = value;
			}
		},
		iconClass()
		{
			return this.notenUebernahme ? 'fa-solid fa-caret-up' : 'fa-solid fa-caret-down';
		}
	},
	methods: {
		newSideMenuEntryHandler: function(payload) {
			this.sideMenuEntries = payload;
		},
		_addButton: function(massnahme, icon, status, title, absage = false)
		{
			let button = document.createElement('button');
			button.className = 'btn btn';
			button.title = this.$p.t('international', title);
			button.innerHTML = "<i class='"+ icon +"' aria-hidden = 'true'></i>";

			this.modalTitle = this.$p.t('international', title);

			let rowData = {
				'massnahme_id' : massnahme,
				'status' : status,
				'absage' : absage
			}

			button.addEventListener('click', (e) =>
			{
				e.stopPropagation();
				if (absage)
					{
						this.formData.massnahme_id = massnahme;
						this.formData.status = status;
						this.formData.absage = absage;
						this.$refs.absageModal.show();
					}
					else
					{
						this.setStatus(rowData);
					}
				}
			);
			return button;
		},
		saveStatusFromModal() {
			const rowData = {
				'massnahme_id' : this.formData.massnahme_id,
				'status' : this.formData.status,
				'absage' : this.formData.absage,
				'absageGrund': this.formData.absageGrund
			};
			this.setStatus(rowData);
		},

		reset: function()
		{
			this.formData.absageGrund = '';
		},
		updateTabulatorFilter()
		{
			this.$refs.massnahmenTable.tabulator.setData();
			this.deleteFilter();
		},
		selectAll() {
			this.$refs.massnahmenTable.tabulator.getRows().filter(row => row.getData().massnahme_status_kurzbz === 'planned')
				.forEach(row => row.select());
		},
		acceptAll() {
			var rows = this.$refs.massnahmenTable.tabulator.getSelectedRows();

			var data = [];
			rows.forEach((row) => {
				if (row.getData().massnahme_status_kurzbz === this.selectableStatus)
				{
					data.push({
						massnahme_id: row.getData().massnahme_zuordnung_id,
						status: 'accepted'
					});
				}
			});
			this.setStatusMulti(data, rows);
		},
		sendMail() {
			const allData = this.$refs.massnahmenTable.tabulator.getRows("active");

			const studentMap = new Map();

			allData.forEach(row => {
				let rowData = row.getData()
				const uid = rowData.student_uid;
				if (!studentMap.has(uid)) {
					studentMap.set(uid, []);
				}
				studentMap.get(uid).push(rowData.massnahme_status_kurzbz);
			});

			let emails = [];

			studentMap.forEach((statusList, uid) => {
				const hasPlannedMeasures = statusList.some(status =>
					status === 'planned' ||
					status === 'accepted' ||
					status === 'confirmed' ||
					status === 'performed'
				);

				if (!hasPlannedMeasures)
				{
					emails.push(`${uid}@technikum-wien.at`);
				}
			});
			if (emails.length > 0)
			{
				if (emails.length > 50)
					this.$fhcAlert.alertWarning(this.$p.t('international', 'mailMeldungzuviele'));
				else
					window.location.href = `mailto:?bcc=${encodeURIComponent(emails)}`;
			}
			else
			{
				this.$fhcAlert.alertSuccess(this.$p.t('international', 'mailMeldung'));
			}
		},
		setStatusMulti (data, rows)
		{
			Vue.$fhcapi.Studiengangsleitung.setStatus(data).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					let data = CoreRESTClient.getData(response.data)
					rows.forEach((row) => {
						row.update(
							{
								'massnahme_status_kurzbz' : data.statusKurz,
								'akzeptieren': data.statusKurz,
								'massnahme_akzeptieren': data.statusKurz,
								'status_bezeichnung' : data.status_bezeichnung
							}
						)
					});
					this.$refs.massnahmenTable.tabulator.rowManager.refreshActiveData();
					this.$refs.massnahmenTable.tabulator.deselectRow();
					this.$fhcAlert.alertSuccess("Erfolgreich gesetzt");
				}
			});
		},
		setStatus (rowData)
		{
			Vue.$fhcapi.Studiengangsleitung.setStatus(rowData).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					let data = CoreRESTClient.getData(response.data)
					this.$refs.massnahmenTable.tabulator.updateRow(
						data.massnahme,
						{
							'massnahme_status_kurzbz' : data.status,
							'document' : data.dms_id,
							'status_bezeichnung' : data.status_bezeichnung,
							'akzeptieren': data.status,
							'massnahme_akzeptieren': data.status,
							'anmerkung_stgl' : data.anmerkung_stgl
						},
					).then(() => {
						this.$refs.massnahmenTable.tabulator.rowManager.refreshActiveData();
						this.$refs.absageModal.hide()
						this.$refs.massnahmenTable.tabulator.deselectRow()
					});
				}
			});
		},
		getStudents(data)
		{
			Vue.$fhcapi.Studiengangsleitung.getStudents(data).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					if (CoreRESTClient.hasData(response.data))
						this._setFilter(CoreRESTClient.getData(response.data));
					else
						this._setFilter(['']);
				}
			});
		},
		_setFilter(uids)
		{
			this.$refs.massnahmenTable.tabulator.setFilter("student_uid", "in", uids);
		},
		_setNotenFilter(uids, withOrgForm = false)
		{
			this.$refs.massnahmenTable.tabulator.setFilter("student_uid", "in", uids);

			if (withOrgForm)
				this.$refs.massnahmenTable.tabulator.addFilter("orgform", "=", this.selectedOrgform)
			var rows = this.$refs.massnahmenTable.tabulator.getRows("active");

			rows.forEach((row) => {
				var rowData = row.getData();
				if (this.filteredUids.includes(rowData.student_uid) && rowData.massnahme_status_kurzbz === this.selectableStatus && !rowData.note)
				{
					row.select();
				}
			});
		},
		_removeOrgFilter(oldOrg)
		{
			this.$refs.massnahmenTable.tabulator.removeFilter("orgform", "=", oldOrg)
		},
		stglTodo()
		{
			this.$refs.massnahmenTable.tabulator.setFilter("massnahme_status_kurzbz", "in", ['planned', 'performed']);
		},
		plannedMore()
		{
			var data = {
				'status': ['planned', 'confirmed', 'performed', 'accepted'],
				'more': true,
				'ects': 5,
				'exists': true,
				'stg' : this.selectedStg
			}
			this.getStudents(data);
		},
		plannedLess()
		{
			var data = {
				'status': ['planned', 'confirmed', 'performed', 'accepted'],
				'more': false,
				'ects': 5,
				'exists': true,
				'stg' : this.selectedStg
			}
			this.getStudents(data);
		},
		confirmedMore()
		{
			var data = {
				'status': ['confirmed'],
				'more': true,
				'ects': 5,
				'exists': true,
				'stg' : this.selectedStg
			}
			this.getStudents(data);
		},
		confirmedLess()
		{
			var data = {
				'status': ['confirmed'],
				'more': false,
				'ects': 5,
				'exists': false,
				'stg' : this.selectedStg
			}
			this.getStudents(data);
		}
		,
		showOpen()
		{
			this.$refs.massnahmenTable.tabulator.setFilter("massnahme_status_kurzbz", "=", 'planned');
		},
		showUploaded()
		{
			this.$refs.massnahmenTable.tabulator.setFilter("massnahme_status_kurzbz", "=", 'performed');
		},
		currentSemester()
		{
			this.$refs.massnahmenTable.tabulator.setFilter("student_studiensemester", "=", this.aktstsem);
		},
		currentOpenSemester()
		{
			this.$refs.massnahmenTable.tabulator.setFilter("studiensemester", "=", this.aktstsem);
		},
		lastSemester()
		{
			this.$refs.massnahmenTable.tabulator.setFilter([
					{field: "student_studiensemester", type: "=", value: this.aktstsem},
					{field: "semester", type: "=", value:  "max_semester"}
				]
			);
		},
		deleteFilter()
		{
			this.selectableStatus = 'confirmed';
			this.$refs.massnahmenTable.tabulator.clearHeaderFilter();
			this.$refs.massnahmenTable.tabulator.clearFilter();
		},
		openNotenUebernahme()
		{
			this.notenUebernahme = true;
			this.selectableStatus = 'confirmed';
			this.$refs.massnahmenTable.tabulator.showColumn('note');
			if (!this.selectedStg || !this.getSem)
				return;

			let data = {
				stg: this.selectedStg,
				stsem: this.getSem
			}
			this.$refs.loader.show();
			this.getOrgForms(data)
				.then(() => this.getLVs(data))
				.then(() => this.loadBenotung(data))
				.then(() => this.$refs.loader.hide());
		},
		async loadBenotung(data)
		{
			await Vue.$fhcapi.Studiengangsleitung.loadBenotung(data).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					if (CoreRESTClient.hasData(response.data))
					{
						let responseData = CoreRESTClient.getData(response.data);
						this.filteredUids = responseData.map(function(obj) {
							return obj['student_uid'];
						});
						this._setNotenFilter(this.filteredUids);
					}
					else
					{
						this._setNotenFilter(['']);
						this.filteredUids = [''];
					}
				}
			});
		},
		closeNotenUebernahme()
		{
			this.selectableStatus = 'planned';
			this.notenUebernahme = false;
			this.$refs.massnahmenTable.tabulator.hideColumn('note');
		},
		toggleNotenUbernahme()
		{
			if (this.selectedStg === '')
				return;
			this.deleteFilter();
			if (!this.notenUebernahme)
				this.openNotenUebernahme();
			else
				this.closeNotenUebernahme();
		},
		async getOrgForms(data)
		{
			await Vue.$fhcapi.Studiengangsleitung.getOrgForms(data).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					if (CoreRESTClient.hasData(response.data))
					{
						this.orgformen = CoreRESTClient.getData(response.data)
						if (this.orgformen.length === 1)
							this.selectedOrgform = this.orgformen[0].orgform_kurzbz;
						else
						{
							this.selectedOrgform = "";
						}
					}
					else
					{
						this.orgformen = [];
						this.selectedOrgform = "";
						this.selectedLv = '';
						this.filteredLvs = [];
					}
				}
				else
				{
					this.orgformen = [];
					this.selectedOrgform = "";
					this.selectedLv = '';
					this.filteredLvs = [];
				}
			})
		},
		async getLVs(data)
		{
			await Vue.$fhcapi.Studiengangsleitung.getLvs(data).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					if (CoreRESTClient.hasData(response.data))
						this.filteredLvs = CoreRESTClient.getData(response.data)
					else
						this.filteredLvs = [];
				}
				else
				{
					this.filteredLvs = [];
					this.selectedLv = '';
				}
			}).then(() => this.filterLVsDropdown());
		},
		filterLVsDropdown()
		{
			const preselectedLv = this.filteredLvs.find(
				lv => lv.bezeichnung === 'International Skills'
					&&
					lv.orgform_kurzbz === this.selectedOrgform
			);
			if (preselectedLv)
			{
				this.selectedLv = preselectedLv.lehrveranstaltung_id;
			}
			else
			{
				this.selectedLv = '';
			}
		},
		noteSetzen ()
		{
			if (this.selectedStg === "" || this.selectedLv === "" || this.getSem === "")
				return this.$fhcAlert.alertWarning("Studiensemester/Orgform und LV auswählen!");

			let selectedData = this.$refs.massnahmenTable.tabulator.getSelectedData();
			let changedData = []

			selectedData.forEach((data,i) => {

				if (!this.filteredUids.includes(data.student_uid))
					return;

				const existingStudent = changedData.find(item => item.student_uid === data.student_uid);

				if (!existingStudent)
				{
					const newData = {
						'stg': this.selectedStg,
						'student_uid' : data.student_uid,
						'lv': this.selectedLv,
						'stsem' : this.getSem
					}
					changedData.push(newData)
				}
			});

			if (changedData.length === 0)
				return this.$fhcAlert.alertWarning("Es wurde bei keiner Person eine Note eingetragen!");

			this.setNote(changedData);
		},
		setNote(changedData) {
			this.$refs.loader.show();
			Vue.$fhcapi.Studiengangsleitung.setNote(changedData).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					let responseData = (CoreRESTClient.getData(response.data));

					if (responseData.count === 0)
					{
						this.$fhcAlert.alertWarning("Es wurde bei keiner Person eine Note eingetragen!");
					}
					else
					{
						responseData.students.forEach(item => {
							this.$refs.massnahmenTable.tabulator.getRows().forEach(row => {
								const rowData = row.getData();
								if (rowData.student_uid === item) {
									row.deselect();
									row.update({
										note: true
									});
								}
							});

						});

						let person = responseData.count === 1 ? 'Person' : 'Personen';
						this.$fhcAlert.alertSuccess(`Es wurde bei ${responseData.count} ${person} eine Note eingetragen!`);
					}
				}
				else
				{
					this.$fhcAlert.alertWarning(CoreRESTClient.getError(response.data));
				}
			}).catch(error => {
				this.$fhcAlert.alertWarning(this.$p.t('ui/fehlerBeimSpeichern'));
			}) .finally(() => {
				this.$refs.loader.hide();
			});
		},
		collapseGroup()
		{
			let oldGroup = this.$refs.massnahmenTable.tabulator.options.groupBy
			this.$refs.massnahmenTable.tabulator.setGroupStartOpen(!this.$refs.massnahmenTable.tabulator.options.groupStartOpen);

			if (this.$refs.massnahmenTable.tabulator.options.groupStartOpen)
			{
				document.getElementById("togglegroup").classList.remove("fa-maximize");
				document.getElementById("togglegroup").classList.add("fa-minimize");
			}
			else
			{
				document.getElementById("togglegroup").classList.remove("fa-minimize");
				document.getElementById("togglegroup").classList.add("fa-maximize");
			}
			this.$refs.massnahmenTable.tabulator.setGroupBy("studiengang");
			this.$refs.massnahmenTable.tabulator.setGroupBy(oldGroup);
		},
		collapseOpenGroup()
		{
			this.$refs.massnahmenTable.tabulator.setGroupStartOpen(false);
		},
	},

	template: `
	<core-base-layout>
		
		<template #main>
			<h3 class="h4">{{ $p.t('international', 'massnahmen') }}</h3>
			<div class="row">
				<div class="col-lg-12">
					<a class="float-end" data-bs-toggle="collapse" data-bs-target="#legende" aria-expanded="true" aria-controls="collapse1">
						{{ $p.t('ui', 'hilfeZuDieserSeite') }}
					</a>
				</div>
			</div>
			<div class="row accordion-collapse collapse" id="legende">
				<div class="col-lg-12">
					<section class="border p-3">
						<div class="row">
							<div class="col-6 highlight">
								<div class="card">
									<div class="card-header">
										Ampelsystem
									</div>
									<ul class="list-group list-group-flush">
										<li class="list-group-item">
											<span class="red box"></span> - {{ $p.t('international', 'ampelRed') }}
										</li>
										<li class="list-group-item">
											<span class="yellow box"></span> - {{ $p.t('international', 'ampelYellow') }}
										</li>
										<li class="list-group-item">
											<span class="orange box"></span> - {{ $p.t('international', 'ampelOrange') }}
										</li>
										<li class="list-group-item">
											<span class="greenyellow box"></span> - {{ $p.t('international', 'ampelGreenyellow') }}
										</li>
										<li class="list-group-item">
											<span class="green box"></span> - {{ $p.t('international', 'ampelGreen') }}
										</li>
									</ul>
								</div>
							</div>
							<div class="col-6 highlight">
								<div class="card statuscard">
									<div class="card-header">
										Status
									</div>
									<table class="table table-sm p-3 statustable">
										<tbody>
											<tr>
												<td class="ps-2">{{ $p.t('international', 'statusGeplant') }}</td>
												<td class="ps-2">{{ $p.t('international', 'statusGeplantDesc') }}</td>
											</tr>
											<tr>
												<td class="ps-2">{{ $p.t('international', 'statusAkzeptiert') }}</td>
												<td class="ps-2">{{ $p.t('international', 'statusAkzeptiertDesc') }}</td>
											</tr>
											<tr>
												<td class="ps-2">{{ $p.t('international', 'statusDurchgefuehrt') }}</td>
												<td class="ps-2">{{ $p.t('international', 'statusDurchgefuehrtDesc') }}</td>
											</tr>
											<tr>
												<td class="ps-2">{{ $p.t('international', 'statusBestaetigt') }}</td>
												<td class="ps-2">{{ $p.t('international', 'statusBestaetigtDesc') }}</td>
											</tr>
											<tr>
												<td class="ps-2">{{ $p.t('international', 'statusAbgelehnt') }}</td>
												<td class="ps-2">{{ $p.t('international', 'statusAbgelehntDesc') }}</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</section>
				</div>
			</div>

			<div class="row mt-4">
				<div class="col-md-2">
					<select v-model="selectedStg" class="form-select">
						<option value="">{{ $p.t('lehre', 'studiengang') }}</option>
						<option v-for="stg in stgs" :value="stg.studiengang_kz">{{ stg.kurzbzlang }}</option>
					</select>
				</div>
				<div class="col-md-2">
					<button v-if="!readonly" @click="toggleNotenUbernahme" :disabled="selectedStg === ''" class="btn btn-primary">Notenübernahme <i :class="iconClass"></i></button>
				</div>
			</div>
			<hr />
			<div class="row" v-if="notenUebernahme">
				<div class="col-md-2">
					<select v-model="getSem" class="form-select">
						<option value="">{{ $p.t('lehre', 'studiensemester') }}</option>
						<option v-for="stsem in stsems" :value="stsem.studiensemester_kurzbz">{{ stsem.studiensemester_kurzbz }}</option>
					</select>
				</div>
				<div class="col-md-2">
					<select v-model="selectedOrgform" class="form-select">
						<option value="">{{ $p.t('lehre', 'organisationsform') }}</option>
						<option v-for="orgform in orgformen" :value="orgform.orgform_kurzbz">{{ orgform.orgform_kurzbz }}</option>
					</select>
				</div>
				<div class="col-md-2">
					<select v-model="selectedLv" class="form-select">
						<option value="">{{ $p.t('lehre', 'lehrveranstaltung') }}</option>
						<option v-for="lv in filteredLvs" :value="lv.lehrveranstaltung_id">{{ lv.bezeichnung }} (LV ID: {{ lv.lehrveranstaltung_id}})</option>
					</select>
				</div>
				
				<div class="col-md-4 justify-content: space-between;">
					<button @click="noteSetzen" class="btn btn-primary" type="button"> Note Übernehmen </button>
				</div>
				<br />
			</div>
		
			<core-filter-cmpt v-if="phrasesLoaded"
				ref="massnahmenTable"
				:tabulator-options="tabulatorOptions"
				:tabulator-events="tabulatorEventHandler"
				@nw-new-entry="newSideMenuEntryHandler"
				:table-only=true
			>
				
			</core-filter-cmpt>
			
			<bs-modal ref="absageModal" class="bootstrap-prompt" dialogClass="modal-lg" @hidden-bs-modal="reset">
				<template #title>{{ modalTitle }}</template>
				<template #default>
					<div class="row">
						<form-input
							type="textarea"
							v-model="formData.absageGrund"
							name="absageGrund"
							rows="5"
							required
							:label="$p.t('international', 'grund')"
						>
						</form-input>
						<div class="form-text text-end">{{formData.absageGrund.length}}</div>
					</div>
				</template>
				<template #footer>
					<button type="button" class="btn btn-primary" @click="saveStatusFromModal">{{ $p.t('ui', 'speichern') }}</button>
				</template>
			</bs-modal>
			
			<br />
			<div v-if="!notenUebernahme">
				<div class="row">
					<div class="col-md-6 d-flex gap-2">
						<button @click="collapseGroup" class="btn btn-outline-secondary" type="button"><i id="togglegroup" class="fa-solid fa-minimize"></i></button>
						<button @click="selectAll" class="btn btn-outline-secondary" type="button"> {{ $p.t('international', 'alleGeplantenMarkieren') }} </button>
						<button v-if="!readonly"  @click="acceptAll" class="btn btn-outline-secondary" type="button"> {{ $p.t('international', 'alleAkzeptierenPlan') }} </button>
						<button @click="sendMail" class="btn btn-outline-secondary" type="button" :title="$p.t('international', 'mailButton')"> {{ $p.t('international', 'mailversenden') }} </button>
					</div>
				</div>
				<br />
				<div class="col-xs-12">
					<div class="btn-toolbar" role="toolbar">
						  <div class="btn-group me-2" role="group" aria-label="First group">
							<button @click="stglTodo" class="btn btn-outline-secondary" type="button" :title="$p.t('international', 'stgtodo')"><i class='fa-solid fas fa-tasks'></i></button>
							<button @click="plannedMore" class="btn btn-outline-secondary" type="button" :title="$p.t('international', 'mehrverplant')"><i class='fa-solid fa-calendar-plus'></i></button>
							<button @click="plannedLess" class="btn btn-outline-secondary" type="button" :title="$p.t('international', 'wenigerverplant')"><i class='fa-solid fa-calendar-minus'></i></button>
							<button @click="confirmedMore" class="btn btn-outline-secondary" type="button" :title="$p.t('international', 'mehrbestaetigt')"><i class='fa-solid fa-calendar-check'></i></button>
							<button @click="confirmedLess" class="btn btn-outline-secondary" type="button" :title="$p.t('international','wenigerbestaetigt')"><i class='fa-solid fa-calendar-times'></i></button>
							<button @click="showOpen" class="btn btn-outline-secondary" type="button" :title="$p.t('international', 'allegeplanten')"><i class='fa-solid fa-calendar'></i></button>
							<button @click="currentOpenSemester" class="btn btn-outline-secondary" type="button" :title="$p.t('international', 'alleMassnahmenJetzt')"><i class='fa-solid fa-clock'></i></button>
							<button @click="currentSemester" class="btn btn-outline-secondary" type="button" :title="$p.t('international', 'alleStudierendeJetzt')"><i class='fa-solid fa-calendar'></i></button>
							<button @click="lastSemester" class="btn btn-outline-secondary" type="button" :title="$p.t('international', 'lastSemester')"><i class='fa-solid fa-clock'></i></button>
							<button @click="showUploaded" class="btn btn-outline-secondary" type="button" :title="$p.t('international', 'alledurchgefuehrten')"><i class='fa-solid fa-check'></i></button>
							<button @click="deleteFilter" class="btn btn-outline-secondary" type="button" :title="$p.t('ui', 'alleAnzeigen')"><i class='fa-solid fa-users'></i></button>
						  </div>
					</div>
				</div>
			</div>
		</template>
	</core-base-layout>
	<fhc-loader ref="loader" :timeout="0"></fhc-loader>
	`
};