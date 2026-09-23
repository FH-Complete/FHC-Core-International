import Massnahmen from "../components/MassnahmenComponent.js";
import Phrasen from '../../../../js/plugins/Phrasen.js';
import Api from '../../../../js/plugins/Api.js';

const massnahmenApp = Vue.createApp({
	components: {
		Massnahmen,
		Phrasen
	}
});
massnahmenApp
	.use(primevue.config.default)
	.use(Phrasen)
	.use(Api)
	.mount("#main");