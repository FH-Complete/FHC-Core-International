import fhc_anwesenheitenapifactory from "../api/fhc-anwesenheitenapifactory.js";
import Studiengangsleitung from "../components/StudiengangsleitungComponent.js";
import Phrasen from '../../../../js/plugin/Phrasen.js';
import FhcAlert from '../../../../js/plugin/FhcAlert.js';

Vue.$fhcapi = fhc_anwesenheitenapifactory;
const studiengangsleitungApp = Vue.createApp({
	components: {
		Studiengangsleitung,
		Phrasen,
		FhcAlert
	}
});

studiengangsleitungApp
	.use(primevue.config.default)
	.use(Phrasen)
	.use(FhcAlert)
	.mount("#main");