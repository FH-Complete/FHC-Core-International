import {CoreFilterCmpt} from '../../../../js/components/filter/Filter.js';
import {CoreRESTClient} from '../../../../js/RESTClient.js';
import CoreBaseLayout from '../../../../js/components/layout/BaseLayout.js';
import BsModal from '../../../../js/components/Bootstrap/Modal.js';
import FormInput from "../../../../js/components/Form/Input.js";
import Dms from "../../../../js/components/Form/Upload/Dms.js";

export default {
	name: 'Student',

	props: {

		massnahmen : {
			type: Array,
			required: true
		},
		studiensemester : {
			type: Object,
			required: true
		}
	},
	components: {
		CoreFilterCmpt,
		CoreBaseLayout,
		BsModal,
		FormInput,
		Dms
	},
	data: function() {
		return {
			loadedCategory: false,
			selectedMassnahme: null,
			modalTitle: '',
			selectedStg: '',
			internationalskills: false,
			nachweis: {
				files: []
			},
			formData: {
				massnahme: null,
				studiensemester: null,
				anmerkung: null
			},
			phrasesLoaded: null,
			sideMenuEntries: {},
			tabulatorEventHandler: [],
		}
	},
	computed: {
		tabulatorOptions() {
			return {
				index: 'massnahme_zuordnung_id',
				ajaxURL: CoreRESTClient._generateRouterURI('/extensions/FHC-Core-International/Student/getData'),
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
				selectable: false,
				placeholder: "Keine Daten verfügbar",
				columns: [
					{title: this.$p.t('international', 'meinMassnahmeplan'), headerSort: false, field: 'bezeichnung', tooltip: (e, cell) => {

							let div = document.createElement('div');
							div.style.whiteSpace = 'pre-wrap';
							div.innerHTML = cell.getData().beschreibung
							return div
						}
					},
					{
						title: this.$p.t('international', 'internationalCredits'),
						field: 'ects',
						hozAlign: "right",
						headerSort: false,
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
						},
						bottomCalcParams:{precision:2}
					},
					{title: this.$p.t('international', 'studiensemesterGeplant'), headerSort: false, field: 'studiensemester_kurzbz'},
					{
						title: this.$p.t('global', 'anmerkung'),
						field: 'anmerkung',
						headerSort: false,
						hozAlign: "center",
						formatter: (cell, formatterParams, onRendered) =>   {
							let value = cell.getValue()
							if (!value)
								return;
							const icon = document.createElement('i');
							icon.className = 'fa fa-info-circle text-primary';
							icon.style.cursor = 'pointer';
							icon.addEventListener('click', () => {
								this.showPopup(value, this.$p.t('global', 'anmerkung'));
							});

							const div = document.createElement('div');
							div.className = "d-flex justify-content-center";
							div.appendChild(icon);
							return div;
						}},
					{title: this.$p.t('global', 'status'), field: 'massnahme_status_kurzbz', headerSort: false,
						sorter: (a, b, aRow, bRow) => this.customSorter(a, b, aRow, bRow),
						formatter: (cell) =>
						{
							let value = cell.getValue();
							switch (value)
							{
								case 'planned' :
									return this.$p.t('international', 'geplanteMassnahmen');
								case 'accepted' :
									return this.$p.t('international', 'akzpetierteMassnahmen');
								case 'performed' :
									return this.$p.t('international', 'durchgefuehrteMassnahmen');
								case 'confirmed' :
									return this.$p.t('international', 'bestaetigteMassnahmen');
								case 'declined' :
									return this.$p.t('international', 'abgelehnteMassnahmen');
							}
						},
						tooltip: (e, cell) => {

							let value = cell.getValue();
							let text = '';
							switch (value)
							{
								case 'planned' :
									text = this.$p.t('international', 'geplanteMassnahmen');
									break;
								case 'accepted' :
									text = this.$p.t('international', 'akzpetierteMassnahmen');
									break;
								case 'performed' :
									text = this.$p.t('international', 'durchgefuehrteMassnahmen');
									break;
								case 'confirmed' :
									text = this.$p.t('international', 'bestaetigteMassnahmen');
									break;
								case 'declined' :
									text = this.$p.t('international', 'abgelehnteMassnahmen');
									break;
							}
							return text;
						}
					},
					{
						title: this.$p.t('international', 'anmerkungstgl'),
						field: 'anmerkung_stgl',
						headerSort: false,
						formatter: (cell, formatterParams, onRendered) =>   {

							let value = cell.getValue()
							if (!value)
								return;
							const icon = document.createElement('i');
							icon.className = 'fa fa-info-circle text-primary';
							icon.style.cursor = 'pointer';
							icon.addEventListener('click', () => {
								this.showPopup(value, this.$p.t('international', 'anmerkungstgl'));
							});

							const div = document.createElement('div');
							div.className = "d-flex justify-content-center";
							div.appendChild(icon);
							return div;
						}
					},
					{
						title: this.$p.t('global', 'dokumentePDF'),
						field: 'dms_id',
						headerSort: false,
						formatter: (cell, formatterParams, onRendered) =>
						{
							const div = document.createElement('div');
							const massnahme = cell.getData().massnahme_zuordnung_id;
							const documentId = cell.getData().dms_id;
							const status = cell.getData().massnahme_status_kurzbz;

							if (documentId === null && status === 'accepted')
							{
								const label = document.createElement('label');
								label.htmlFor = 'fileNachweis_' + massnahme;
								label.className = 'btn btn';
								label.innerHTML = "<i class='fa fa-upload fa-1x' aria-hidden='true'></i>";

								const input = document.createElement('input');
								input.type = 'file';
								input.name = 'uploadfile';
								input.id = 'fileNachweis_' + massnahme;
								input.accept = '.pdf';
								input.className = 'fileNachweis hidden';
								input.style.display = 'none';
								label.appendChild(input);
								div.appendChild(label);
								input.addEventListener('change', () =>  {
									if (!input.files.length) {
										alert('Please select a file');
										return;
									}

									const file = input.files[0];
									const data = new FormData();
									data.append('file', file);
									data.append('massnahmenZuordnung', massnahme);

									this.uploadNachweis(data);
								});
							}
							else if (documentId !== null)
							{
								const downloadButton = document.createElement('button');
								downloadButton.className = 'btn btn';
								downloadButton.innerHTML = "<i class='fa-solid fa-download' aria-hidden = 'true'></i>";
								downloadButton.addEventListener('click', () => window.location.href = CoreRESTClient._generateRouterURI('extensions/FHC-Core-International/Student/studentDownloadNachweis?massnahmenZuordnung=' + massnahme));

								div.appendChild(downloadButton);

								if (status !== 'confirmed' && status !== 'declined') {
									const deleteButton = document.createElement('button');
									deleteButton.className = 'btn btn';
									deleteButton.innerHTML = "<i class='fa-solid fa-trash' aria-hidden = 'true'></i>";
									deleteButton.addEventListener('click', () => {
										const data = { 'massnahmenZuordnung': massnahme };
										this.deleteNachweis(data);
									});

									div.appendChild(document.createTextNode(' '));
									div.appendChild(deleteButton);
								}
							} else {
								return '-';
							}

							return div;
						},
						tooltip: () => {
							return this.$p.t('ui', 'hochladen');
						}
					},
					{
						title: this.$p.t('international', 'massnahmeLoeschen'),
						field: 'massnahmen_status',
						headerSort: false,
						formatter: (cell, formatterParams, onRendered) =>
						{
							let status = cell.getData().massnahme_status_kurzbz;

							if (status !== 'confirmed' && status !== 'declined')
							{
								let deleteMassnahme = this._addButton('fa-solid fa-remove', 'massnahmeLoeschen');
								deleteMassnahme.addEventListener('click', () => this.deleteStudentMassnahme(cell));
								return deleteMassnahme;
							}
							else
								return '-';
						}
					},
				],
				persistenceID: "15.04.2025",
			}
		},

	},
	async created() {
		await this.$p.loadCategory(['ui', 'international', 'global', 'lehre']).then(() => {
			this.phrasesLoaded = true;
		});
	},
	methods: {
		newSideMenuEntryHandler: function(payload) {
			this.sideMenuEntries = payload;
		},
		showPopup(value, title)
		{
			BsModal.popup(
				Vue.h('div', {
					style: {
						whiteSpace: 'pre-wrap'
					}
				}, value),
				{
					centered: true,
					size: 'lg',
					backdrop: true,
				},
				title
			);

		},
		load: function()
		{
			Vue.$fhcapi.Student.getData().then(response => {

				if (CoreRESTClient.isSuccess(response.data))
				{
					this.$refs.massnahmenStudentTable.tabulator.setData(CoreRESTClient.getData(response.data).retval);
				}
			});
		},
		_addButton: function(icon, title)
		{
			let button = document.createElement('button');
			button.className = 'btn btn';
			button.title = this.$p.t('international', title);
			button.innerHTML = "<i class='"+ icon +"' aria-hidden = 'true'></i>";
			return button;
		},
		uploadNachweis: function(data)
		{
			Vue.$fhcapi.Student.uploadNachweis(data).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					let data = CoreRESTClient.getData(response.data)
					this.$refs.massnahmenStudentTable.tabulator.updateRow(
						data.massnahme,
						{
							'dms_id' : data.dms_id,
							'massnahme_status_kurzbz': 'performed'
						}
					).then(() => this.setOrder());
				}
			});
		},
		deleteNachweis: function(data)
		{
			Vue.$fhcapi.Student.deleteNachweis(data).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					let data = CoreRESTClient.getData(response.data)
					this.$refs.massnahmenStudentTable.tabulator.updateRow(
						data,
						{
							'dms_id' : null,
							'massnahme_status_kurzbz': 'accepted'
						},
					).then(() => this.setOrder());
				}
			});
		},
		reset: function()
		{
			this.formData.massnahme = null;
			this.formData.studiensemester = null;
			this.formData.anmerkung = null;
		},
		addMassnahmeContainer()
		{
			this.$refs.addMassnahmeModel.show();
		},
		addMassnahme()
		{
			Vue.$fhcapi.Student.addMassnahme(this.formData).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					let data = CoreRESTClient.getData(response.data);

					this.$refs.massnahmenStudentTable.tabulator.addRow({
						massnahme_zuordnung_id: data.massnahme_zuordnung_id,
						bezeichnung: data.bezeichnung,
						massnahme_status_kurzbz: 'planned',
						dms_id: null,
						studiensemester_kurzbz: data.studiensemester,
						ects: data.ects,
						massnahme_id: data.massnahme_id,
						status: 'planned',
						anmerkung: data.anmerkung
					}, true);

					this.reset();
					this.$refs.addMassnahmeModel.hide();
				}
			}).then(() => {
				this.setOrder()
			});
		},
		async deleteStudentMassnahme (cell)
		{
			var status = cell.getData().massnahme_status_kurzbz;

			if (status === 'accepted' || status === 'performed')
				if (await this.$fhcAlert.confirmDelete() === false) return;

			var data = {
				'massnahmenZuordnung' : cell.getData().massnahme_zuordnung_id
			}

			Vue.$fhcapi.Student.deleteMassnahme(data).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					let data = CoreRESTClient.getData(response.data)
					this.$refs.massnahmenStudentTable.tabulator.deleteRow(data.massnahme_zuordnung_id)
				}
			});
		},
		showInfoText()
		{
			this.internationalskills = !this.internationalskills;
		},
		customSorter(a, b, aRow, bRow)
		{
			const order = ["planned", "accepted", "performed", "confirmed", "declined"];
			let indexA = order.indexOf(a);
			let indexB = order.indexOf(b);

			if (indexA === indexB)
			{
				let nameA = aRow.getData().bezeichnung.toLowerCase();
				let nameB = bRow.getData().bezeichnung.toLowerCase();
				return nameA.localeCompare(nameB);
			}

			return (indexA === -1 ? order.length : indexA) - (indexB === -1 ? order.length : indexB);
		},
		setOrder()
		{
			this.$refs.massnahmenStudentTable.tabulator.setSort([
				{ column: 'massnahme_status_kurzbz', dir: 'asc'},
			]);
		}

	},

	template: `
	<core-base-layout>
		<template #main>
			<h3 class="h4">{{ $p.t('international', 'internationalskills') }}
				<i class="fa fa-info-circle text-right" @click="showInfoText"></i>
			</h3>
			<div class="row" v-show="internationalskills">
				<div class="col-xl-6">
					<div class="alert alert-info" v-html="$p.t('international', 'internationalbeschreibung')"></div>
				</div>
				<div class="col-6 highlight">
					<div class="card statuscard">
						<div class="card-header">
							Status
						</div>
						<table class="table table-sm p-3 statustable">
							<tbody>
								<tr>
									<td class="ps-2">{{ $p.t('international', 'geplanteMassnahmen') }}</td>
									<td class="ps-2">{{ $p.t('international', 'geplanteMassnahmenDesc') }}</td>
								</tr>
								<tr>
									<td class="ps-2">{{ $p.t('international', 'akzpetierteMassnahmen') }}</td>
									<td class="ps-2">{{ $p.t('international', 'statusAkzeptiertDesc') }}</td>
								</tr>
								<tr>
									<td class="ps-2">{{ $p.t('international', 'durchgefuehrteMassnahmen') }}</td>
									<td class="ps-2">{{ $p.t('international', 'statusDurchgefuehrtDesc') }}</td>
								</tr>
								<tr>
									<td class="ps-2">{{ $p.t('international', 'bestaetigteMassnahmen') }}</td>
									<td class="ps-2">{{ $p.t('international', 'statusBestaetigtDesc') }}</td>
								</tr>
								<tr>
									<td class="ps-2">{{ $p.t('international', 'abgelehnteMassnahmen') }}</td>
									<td class="ps-2">{{ $p.t('international', 'statusAbgelehntDesc') }}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<hr />
			<core-filter-cmpt
				v-if="phrasesLoaded"
				ref="massnahmenStudentTable"
				:tabulator-options="tabulatorOptions"
				:tabulator-events="tabulatorEventHandler"
				@nw-new-entry="newSideMenuEntryHandler"
				:table-only=true
				new-btn-label="Massnahme"
				new-btn-show
				@click:new="addMassnahmeContainer"
			></core-filter-cmpt>

			<bs-modal ref="addMassnahmeModel" class="bootstrap-prompt" dialogClass="modal-xl" @hidden-bs-modal="reset">
				<template #title>{{ $p.t('international', 'addMassnahme') }}</template>
				<template #default>
					<div class="row row-cols-2">
						<div class="col">
							<form-input
								type="select"
								v-model="formData.massnahme"
								name="massnahme"
								:label="$p.t('international', 'massnahmen')"
							>
								<option value="null" disabled selected>{{$p.t('international', 'massnahmen')}}</option> 
								<option v-for="massnahme in massnahmen" :value="massnahme" :title="massnahme.bezeichnung">({{massnahme.ects}} International Credits) {{ massnahme.bezeichnung }}</option>
							</form-input>
						</div>
						<div class="col">
							<form-input
								type="select"
								v-model="formData.studiensemester"
								name="studiensemester"
								:label="$p.t('lehre', 'studiensemester')"
							>
								<option value="null" disabled selected>{{ $p.t('lehre', 'studiensemester') }}</option> 
								<option v-for="ststem in studiensemester" :value="ststem.studiensemester_kurzbz">{{ ststem.studiensemester_kurzbz }}</option>
							</form-input>
						</div>
					</div>
					<div class="row row-cols-2">
						<div class="col">
							<div class="form-text" style="white-space: pre-line;" v-if="this.formData.massnahme !== null" v-html="this.formData.massnahme.beschreibung">
							</div>
						</div>
						<div class="col">
							<form-input
								type="textarea"
								v-model="formData.anmerkung"
								name="anmerkung"
								rows="2"
								required
								:label="$p.t('global', 'anmerkung')"
								>
							</form-input>
						</div>
					</div>
				</template>
				<template #footer>
					<button type="button" class="btn btn-primary" @click="addMassnahme">{{$p.t('ui', 'speichern')}}</button>
				</template>
			</bs-modal>
		</template>
	</core-base-layout>
	`
};