import fhcapifactory from "../api/fhcapifactory.js";
import Massnahmen from "../components/MassnahmenComponent.js";
import Phrasen from '../../../../js/plugin/Phrasen.js';
import FhcAlert from '../../../../js/plugin/FhcAlert.js';

Vue.$fhcapi = fhcapifactory;
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