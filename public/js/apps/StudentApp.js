import fhc_anwesenheitenapifactory from "../api/fhc-anwesenheitenapifactory.js";
import Student from "../components/StudentComponent.js";
import Phrasen from '../../../../js/plugin/Phrasen.js';
import FhcAlert from '../../../../js/plugin/FhcAlert.js';

Vue.$fhcapi = fhc_anwesenheitenapifactory;

const studentApp = Vue.createApp({
	components: {
		Student,
		Phrasen,
		FhcAlert
	}
});
studentApp
	.use(primevue.config.default)
	.use(Phrasen)
	.use(FhcAlert)
	.mount("#main");