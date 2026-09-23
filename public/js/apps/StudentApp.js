import Student from "../components/StudentComponent.js";
import Phrasen from '../../../../js/plugins/Phrasen.js';
import Api from '../../../../js/plugins/Api.js';

const studentApp = Vue.createApp({
	components: {
		Student,
		Phrasen,
	}
});
studentApp
	.use(primevue.config.default)
	.use(Phrasen)
	.use(Api)
	.mount("#main");
