import fhcapifactory from "../api/fhcapifactory.js";
import Student from "../components/StudentComponent.js";
import Phrasen from '../../../../js/plugin/Phrasen.js';
import FhcAlert from '../../../../js/plugin/FhcAlert.js';

Vue.$fhcapi = (Vue?.$fhcapi === undefined) ? fhcapifactory : {...Vue.$fhcapi, ...fhcapifactory};

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
