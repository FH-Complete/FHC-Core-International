import Studiengangsleitung from "../components/StudiengangsleitungComponent.js";
import Phrasen from '../../../../js/plugins/Phrasen.js';
import Api from '../../../../js/plugins/Api.js';

const studiengangsleitungApp = Vue.createApp({
	components: {
		Studiengangsleitung,
		Phrasen,
	}
});

studiengangsleitungApp
	.use(primevue.config.default)
	.use(Phrasen)
	.use(Api)
	.mount("#main");