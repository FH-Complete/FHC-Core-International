import {CoreRESTClient} from "../../../../../public/js/RESTClient";

export default {
	handleSave(data)
	{
		try {
			return CoreRESTClient.post('/extensions/FHC-Core-International/Massnahmen/handleSave', data);
		} catch (error) {
			throw error;
		}
	},
	deleteMassnahme(data)
	{
		try {
			return CoreRESTClient.post('/extensions/FHC-Core-International/Massnahmen/deleteMassnahme', data);
		} catch (error) {
			throw error;
		}
	},

};