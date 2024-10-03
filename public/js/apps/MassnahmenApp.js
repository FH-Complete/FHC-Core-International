import fhc_anwesenheitenapifactory from "../api/fhc-anwesenheitenapifactory.js";
import Massnahmen from "../components/MassnahmenComponent.js";
import Phrasen from '../../../../js/plugin/Phrasen.js';
import FhcAlert from '../../../../js/plugin/FhcAlert.js';

Vue.$fhcapi = fhc_anwesenheitenapifactory;
const massnahmenApp = Vue.createApp({
	components: {
		Massnahmen,
		Phrasen,
		FhcAlert
	}
});
massnahmenApp
	.use(primevue.config.default)
	.use(Phrasen)
	.use(FhcAlert)
	.mount("#main");