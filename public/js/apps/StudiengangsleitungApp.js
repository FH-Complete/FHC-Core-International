import Studiengangsleitung from "../components/StudiengangsleitungComponent.js";
import Phrasen from '../../../../js/plugin/Phrasen.js';
import FhcAlert from '../../../../js/plugin/FhcAlert.js';
import fhcapifactory from "../api/fhcapifactory.js";

Vue.$fhcapi = fhcapifactory;
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