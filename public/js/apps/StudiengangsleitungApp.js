import Studiengangsleitung from "../components/StudiengangsleitungComponent.js";
import Phrasen from '../../../../js/plugin/Phrasen.js';
import FhcAlert from '../../../../js/plugins/FhcAlert.js';
import fhcapifactory from "../api/fhcapifactory.js";

Vue.$fhcapi = fhcapifactory;
const studiengangsleitungApp = Vue.createApp({
	components: {
		Studiengangsleitung,
		Phrasen,
	}
});

studiengangsleitungApp
	.use(primevue.config.default)
	.use(Phrasen)
	.mount("#main");
