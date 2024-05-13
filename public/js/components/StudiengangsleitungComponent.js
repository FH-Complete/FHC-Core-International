import {CoreFilterCmpt} from '../../../../js/components/filter/Filter.js';
import {CoreRESTClient} from '../../../../js/RESTClient.js';
import CoreBaseLayout from '../../../../js/components/layout/BaseLayout.js';
import BsModal from '../../../../js/components/Bootstrap/Modal.js';
import FormInput from "../../../../js/components/Form/Input.js";

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
		lvs : {
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
		FormInput
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

			sideMenuEntries: {},
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
					innerDiv.style.backgroundColor = color;
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
					{title:  this.$p.t('person', 'uid'), field: 'student_uid', headerFilter: true, width: 150},
					{title: this.$p.t('person', 'vorname'), field: 'vorname', headerFilter: true, width: 120},
					{title: this.$p.t('person', 'nachname'), field: 'nachname', headerFilter: true, width: 120},
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
					{title: this.$p.t('ui', 'bezeichnung'), field: 'bezeichnung', headerFilter: true, width: 400},
					{title: this.$p.t('global', 'status'), field: 'status_bezeichnung', headerFilter: true, width: 100},
					{title: this.$p.t('global', 'anmerkung'), field: 'anmerkung', headerFilter: true},
					{title: this.$p.t('international', 'anmerkungstgl'), field: 'anmerkung_stgl', headerFilter: true, width: 220},
					{title: this.$p.t('lehre', 'semester'), field: 'semester', headerFilter: true, width: 100},
					{title: this.$p.t('international', 'studiensemesterGeplant'), field: 'studiensemester', headerFilter: true, width: 220},
					{
						title: this.$p.t('international', 'planAkzeptieren'),
						field: 'akzeptieren',
						headerFilter: true,
						width: 150,
						formatter: (cell, formatterParams, onRendered) =>
						{
							var massnahme = cell.getData().massnahme_zuordnung_id;
							var status = cell.getData().massnahme_status_kurzbz;

							if (status !== 'confirmed' && status !== 'declined' && this.readonly === false)
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
						}
					},
					{
						title: this.$p.t('international', 'bestaetigungAkzeptieren'),
						field: 'massnahme_akzeptieren',
						headerFilter: true,
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
								downloadNachweis.title = this.$p.t("international", "ectsBestaetigt");
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
						width: 200,
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
							return this.$p.t('international', 'ectsBestaetigt') + " " + sum + ".00"
						}
					},
					{title: this.$p.t('lehre', 'studiensemester'), field: 'student_studiensemester', headerFilter: true},
					{
						title: this.$p.t('global', 'kontakt'),
						field: 'kontakt',
						headerFilter: true,
						width: 100,
						formatter: (cell, formatterParams, onRendered) =>
						{
							return "<a href='mailto:"+ cell.getData().student_uid +"@technikum-wien.at?subject=" + cell.getData().bezeichnung + "'><i class='fa-solid fa-envelope'></i></a>"
						}
					},
				],
			}
		},
		getSem: {
			get() {
				return this.selectedStsem !== "" ? this.selectedStsem : this.aktstsem;
			},
			set(value) {
				this.selectedStsem = value;
			}
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

			button.addEventListener('click', () =>
			{
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
			this.notenUebernahme = false;
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
						this.$refs.massnahmenTable.tabulator.scrollToRow(data.massnahme, 'top', true);
					});
					this.$refs.absageModal.hide()
					this.$refs.massnahmenTable.tabulator.deselectRow()
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
			this.$refs.massnahmenTable.tabulator.setFilter("student_studiensemester", "=", this.stsem);
		},
		currentOpenSemester()
		{
			this.$refs.massnahmenTable.tabulator.setFilter("studiensemester", "=", this.stsem);
		},
		lastSemester()
		{
			this.$refs.massnahmenTable.tabulator.setFilter([
					{field: "student_studiensemester", type: "=", value: this.stsem},
					{field: "semester", type: "=", value:  "max_semester"}
				]
			);
		},
		deleteFilter()
		{
			this.selectableStatus = 'planned';
			this.$refs.massnahmenTable.tabulator.hideColumn('note');
			this.$refs.massnahmenTable.tabulator.clearFilter();
		},
		openNotenUebernahme()
		{
			this.notenUebernahme = true;

			let data = {
				stg: this.selectedStg,
				stsem: this.getSem
			}
			Vue.$fhcapi.Studiengangsleitung.loadBenotung(data).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					if (CoreRESTClient.hasData(response.data))
					{
						let responseData = CoreRESTClient.getData(response.data);
						var uids = responseData.map(function(obj) {
							return obj['student_uid'];
						});
						this._setFilter(uids);

						this.selectableStatus = 'confirmed';
						var rows = this.$refs.massnahmenTable.tabulator.getRows();
						rows.forEach((row) => {
							var rowData = row.getData();
							if (uids.includes(rowData.student_uid) && rowData.massnahme_status_kurzbz === this.selectableStatus && !rowData.note)
							{
								row.select();
							}
						});
						this.$refs.massnahmenTable.tabulator.showColumn('note');
					}
					else
					{
						this._setFilter(['']);
					}
				}
			});
		},
		closeNotenUebernahme()
		{
			this.notenUebernahme = false;
			this.deleteFilter();
		},
		toggleNotenUbernahme()
		{
			if (this.selectedStg === '')
				return;

			if (!this.notenUebernahme)
				this.openNotenUebernahme();
			else
				this.closeNotenUebernahme();
		},
		updateLVsDropdown()
		{
			if (this.selectedStg && this.getSem)
			{
				this.filteredLvs = this.lvs.filter(lv => lv.studiengang_kz === this.selectedStg);

				const preselectedLv = this.filteredLvs.find(lv => lv.bezeichnung === 'International Skills');
				if (preselectedLv)
				{
					this.selectedLv = preselectedLv.lehrveranstaltung_id;
				}
				else
				{
					this.selectedLv = '';
				}
			}
			else
			{
				this.filteredLvs = [];
				this.selectedLv = '';
			}
		},
		noteSetzen ()
		{
			let selectedData = this.$refs.massnahmenTable.tabulator.getSelectedData();
			let changedData = []
			selectedData.forEach((data,i) => {
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

			this.setNote(changedData);
		},
		setNote(changedData){
			Vue.$fhcapi.Studiengangsleitung.setNote(changedData).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					changedData.forEach(item => {
						this.$refs.massnahmenTable.tabulator.getRows().forEach(row => {
							const rowData = row.getData();
							if (rowData.student_uid === item.student_uid) {
								row.deselect();
								row.update({
									note: true
								});
							}
						});
					});
					this.$fhcAlert.alertSuccess("Note erfolgreich gesetzt");
				}
			});
		},
	},

	template: `
	<core-base-layout
		:title="$p.t('international', 'massnahmen')">
		<template #main>
			<div class="row">
				<div class="col-md-2">
					<select v-model="selectedStg" class="form-control" @change="updateTabulatorFilter(); updateLVsDropdown()">
						<option value="">{{ $p.t('lehre', 'studiengang') }}</option>
						<option v-for="stg in stgs" :value="stg.studiengang_kz">{{ stg.kurzbzlang }}</option>
					</select>
				</div>
				<div class="col-md-2">
					<button @click="toggleNotenUbernahme" class="btn btn-secondary btn-sm">Notenübernahme</button>
				</div>
			</div>
			<hr />
			<div class="row" v-if="notenUebernahme">
				<div class="col-md-2">
					<select v-model="getSem" class="form-control" @change="updateLVsDropdown(); openNotenUebernahme()">
						<option value="">{{ $p.t('lehre', 'studiensemester') }}</option>
						<option v-for="stsem in stsems" :value="stsem.studiensemester_kurzbz">{{ stsem.studiensemester_kurzbz }}</option>
					</select>
				</div>
				<div class="col-md-2">
					<select v-model="selectedLv" class="form-control">
						<option value="">{{ $p.t('lehre', 'lehrveranstaltung') }}</option>
						<option v-for="lv in filteredLvs" :value="lv.lehrveranstaltung_id">{{ lv.bezeichnung }}</option>
					</select>
				</div>
				
				<div class="col-md-4 justify-content: space-between;">
					<button @click="noteSetzen" class="btn btn-secondary" type="button"> Note Übernehmen </button>
				</div>
			</div>
			
			<core-filter-cmpt v-if="phrasesLoaded"
				ref="massnahmenTable"
				:tabulator-options="tabulatorOptions"
				:tabulator-events="tabulatorEventHandler"
				@nw-new-entry="newSideMenuEntryHandler"
				:table-only=true
				:hideTopMenu=false
			></core-filter-cmpt>
			
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
			<div class="row">

				<div class="col-md-6 d-flex gap-2">
					<button @click="selectAll" class="btn btn-secondary btn-sm" type="button"> {{ $p.t('international', 'alleGeplantenMarkieren') }} </button>
					<button @click="acceptAll" class="btn btn-secondary btn-sm" type="button"> {{ $p.t('international', 'alleAkzeptierenPlan') }} </button>
				</div>
			</div>
			<br />
			<div class="col-xs-12">
				<div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
					  <div class="btn-group me-2" role="group" aria-label="First group">
						<button @click="plannedMore" class="btn btn-secondary btn-sm" type="button" :title="$p.t('international', 'mehrverplant')"><i class='fa-solid fa-calendar-plus'></i></button>
						<button @click="plannedLess" class="btn btn-secondary btn-sm" type="button" :title="$p.t('international', 'wenigerverplant')"><i class='fa-solid fa-calendar-minus'></i></button>
						<button @click="confirmedMore" class="btn btn-secondary btn-sm" type="button" :title="$p.t('international', 'mehrbestaetigt')"><i class='fa-solid fa-calendar-check'></i></button>
						<button @click="confirmedLess" class="btn btn-secondary btn-sm" type="button" :title="$p.t('international','wenigerbestaetigt')"><i class='fa-solid fa-calendar-times'></i></button>
						<button @click="showOpen" class="btn btn-secondary btn-sm" type="button" :title="$p.t('international', 'allegeplanten')"><i class='fa-solid fa-calendar'></i></button>
						<button @click="currentOpenSemester" class="btn btn-secondary btn-sm" type="button" :title="$p.t('international', 'alleMassnahmenJetzt')"><i class='fa-solid fa-clock'></i></button>
						<button @click="currentSemester" class="btn btn-secondary btn-sm" type="button" :title="$p.t('international', 'alleStudierendeJetzt')"><i class='fa-solid fa-calendar'></i></button>
						<button @click="lastSemester" class="btn btn-secondary btn-sm" type="button" :title="$p.t('international', 'lastSemester')"><i class='fa-solid fa-clock'></i></button>
						<button @click="showUploaded" class="btn btn-secondary btn-sm" type="button" :title="$p.t('international', 'alledurchgefuehrten')"><i class='fa-solid fa-check'></i></button>
						<button @click="deleteFilter" class="btn btn-secondary btn-sm" type="button" :title="$p.t('ui', 'alleAnzeigen')"><i class='fa-solid fa-users'></i></button>
					  </div>
				</div>
			</div>
		</template>
		
	</core-base-layout>
	`
};