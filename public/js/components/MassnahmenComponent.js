import {CoreFilterCmpt} from '../../../../js/components/filter/Filter.js';
import {CoreRESTClient} from '../../../../js/RESTClient.js';
import CoreBaseLayout from '../../../../js/components/layout/BaseLayout.js';
import BsModal from '../../../../js/components/Bootstrap/Modal.js';
import FormInput from "../../../../js/components/Form/Input.js";

export default {
	name: 'Massnahmen',
	components: {
		CoreFilterCmpt,
		CoreBaseLayout,
		BsModal,
		FormInput
	},
	data: function() {
		return {
			modalTitle: '',
			formData: {
				bezeichnung: null,
				bezeichnungeng: null,
				beschreibung: null,
				beschreibungeng: null,
				aktiv: null,
				ects: null,
				massnahme_id: null
			},
			editMode: null,
			phrasesLoaded: null,
			sideMenuEntries: {},
			tabulatorEventHandler: []
		}
	},
	computed: {
		tabulatorOptions() {
			return {
				index: 'massnahme_id',
				ajaxURL: CoreRESTClient._generateRouterURI('/extensions/FHC-Core-International/Massnahmen/load'),
				ajaxResponse: (url, params, response)=>  {
					if (CoreRESTClient.isSuccess(response))
					{
						if (CoreRESTClient.hasData(response))
							return CoreRESTClient.getData(response);
						else
							return [];
					}
				},
				height: "50%",
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
				selectablePersistence: false,
				columns: [
					{title: 'Bezeichnung', field: 'bezeichnungshow'},
					{title: 'Beschreibung', field: 'beschreibungshow'},
					{title: 'International Credits', field: 'ects'},
					{
						title: 'Aktiv',
						field: 'aktiv',
						formatter: "tickCross",
						headerFilter: "tickCross",
						headerFilterParams: {"tristate": true},
						hozAlign: "center",
						formatterParams: {
							tickElement: '<i class="fa fa-check text-success"></i>',
							crossElement: '<i class="fa fa-xmark text-danger"></i>'
						}
					},
					{
						title: 'Details',
						formatter: (cell, formatterParams, onRendered) =>

						{
							var link = document.createElement('a');
							link.title = 'Details';
							link.innerHTML = 'Details';
							link.href = '#'; // Hier könnte die URL zum Bearbeiten angegeben werden
							link.addEventListener('click', () => this.editMassnahme(cell.getData()));
							return link;
						}
					},
				],
			}
		},
	},
	async created() {
		await this.$p.loadCategory(['ui', 'international', 'lehre']).then(() => {
			this.phrasesLoaded = true;
		});
	},

	methods: {
		newSideMenuEntryHandler: function(payload) {
			this.sideMenuEntries = payload;
		},
		editMassnahme: function (massnahme) {
			this.editMode = true;
			this.formData.bezeichnung = massnahme.bezeichnung;
			this.formData.bezeichnungeng = massnahme.bezeichnungeng;
			this.formData.beschreibung = massnahme.beschreibung;
			this.formData.beschreibungeng = massnahme.beschreibungeng;
			this.formData.aktiv = massnahme.aktiv;
			this.formData.ects = massnahme.ects;
			this.formData.massnahme_id = massnahme.massnahme_id;
			this.$refs.showMassnahmeModal.show();
		},
		showMassnahmeContainer()
		{
			this.reset();
			this.editMode = false;
			this.$refs.showMassnahmeModal.show();
		},
		reset()
		{
			this.formData.bezeichnung = null;
			this.formData.bezeichnungeng = null;
			this.formData.beschreibung = null;
			this.formData.beschreibungeng = null;
			this.formData.aktiv = null;
			this.formData.ects = null;
			this.formData.massnahme_id = null;
		},
		remove()
		{
			let data = {
				massnahme_id: this.formData.massnahme_id
			}

			Vue.$fhcapi.Massnahme.deleteMassnahme(data).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					this.$fhcAlert.alertSuccess("Erfolgreich gelöscht");
					this.$refs.massnahmeTable.tabulator.deleteRow(this.formData.massnahme_id);
					this.$refs.showMassnahmeModal.hide();
					this.reset();
				}
				else
				{
					this.$fhcAlert.alertWarning(response.data.retval);
				}

			});
		},
		save()
		{
			Vue.$fhcapi.Massnahme.handleSave(this.formData).then(response => {
				if (CoreRESTClient.isSuccess(response.data))
				{
					this.$fhcAlert.alertSuccess("Erfolgreich gespeichert");
					if (this.formData.massnahme_id === null)
					{
						let newMassnahme = CoreRESTClient.getData(response.data).retval[0];
						this.$refs.massnahmeTable.tabulator.addRow(
							{
								ects: newMassnahme.ects,
								massnahme_id: newMassnahme.massnahme_id,
								aktiv: newMassnahme.aktiv,
								bezeichnungshow: newMassnahme.bezeichnungshow,
								beschreibungshow: newMassnahme.beschreibungshow,
								bezeichnung: newMassnahme.bezeichnung,
								bezeichnungeng: newMassnahme.bezeichnungeng,
								beschreibung: newMassnahme.beschreibung,
								beschreibungeng: newMassnahme.beschreibungeng,
							}
						)
					}
					else
					{
						this.$refs.massnahmeTable.tabulator.updateRow(
							this.formData.massnahme_id,
							this.formData
						)
					}
					this.$refs.showMassnahmeModal.hide();
					this.reset();
				}
				else
				{
					this.$fhcAlert.alertWarning(response.data.retval);
				}

			});
		}
	},
	template: `
	<core-base-layout
		:subtitle="$p.t('global', 'beschreibung')">
		<template #main>
			<h3 class="h4">{{ $p.t('international', 'internationalskills') }}</h3>
			<core-filter-cmpt
				v-if="phrasesLoaded"
				ref="massnahmeTable"
				:tabulator-options="tabulatorOptions"
				:tabulator-events="tabulatorEventHandler"
				@nw-new-entry="newSideMenuEntryHandler"
				:table-only=true
				new-btn-label="Massnahme"
				new-btn-show
				@click:new="showMassnahmeContainer"></core-filter-cmpt>

			<bs-modal ref="showMassnahmeModal" class="bootstrap-prompt" dialogClass="modal-xl" @hidden-bs-modal="reset">
				<template #title>Massnahme</template>
				<template #default>
					<div class="row row-cols-3">
						<div class="col">
							<form-input
								v-model="formData.bezeichnung"
								name="bezeichnung"
								:label="$p.t('international', 'bezeichnung')"
							/>
						</div>
						<div class="col">
							<form-input
								v-model="formData.bezeichnungeng"
								name="bezeichnungeng"
								:label="$p.t('international', 'bezeichnungeng')"
							/>
						</div>
						<div class="col">
							<form-input
								type="number"
								v-model="formData.ects"
								name="ects"
								:label="$p.t('international', 'internationalCredits')"
							/>
						</div>
					</div>
					<div class="row row-cols-3">
						<div class="col">
							<form-input
								type="textarea"
								v-model="formData.beschreibung"
								name="beschreibung"
								rows="5"
								required
								:label="$p.t('international', 'beschreibung')"
								>
							</form-input>
						</div>
						<div class="col">
							<form-input
								type="textarea"
								v-model="formData.beschreibungeng"
								name="beschreibungeng"
								rows="5"
								required
								:label="$p.t('international', 'beschreibungeng')"
								>
							</form-input>
						</div>
						<div class="col">
							<form-input
								type="checkbox"
								v-model="formData.aktiv"
								name="ects"
								:label="$p.t('global', 'aktiv')"
							/>
						</div>
					</div>
					
				</template>
				<template #footer>
					<button type="button" class="btn btn-danger" @click="remove" v-if="editMode">{{$p.t('ui', 'loeschen')}}</button>
					<button type="button" class="btn btn-primary" @click="save">{{$p.t('ui', 'speichern')}}</button>
				</template>
			</bs-modal>
		</template>
	</core-base-layout>
	`
};