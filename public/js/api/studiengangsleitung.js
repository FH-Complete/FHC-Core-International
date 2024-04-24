import {CoreRESTClient} from "../../../../../public/js/RESTClient";

export default {
	setStatus(data)
	{
		try {
			return CoreRESTClient.post('/extensions/FHC-Core-International/Studiengangsleitung/setStatus', data);
		} catch (error) {
			throw error;
		}
	},
	setNote(data)
	{
		try {
			return CoreRESTClient.post('/extensions/FHC-Core-International/Studiengangsleitung/setNote', data);
		} catch (error) {
			throw error;
		}
	},
	getStudents(data)
	{
		try {
			return CoreRESTClient.get('extensions/FHC-Core-International/Studiengangsleitung/getStudents', data);
		} catch (error) {
			throw error;
		}
	},
	loadBenotung(data)
	{
		try {
			return CoreRESTClient.get('extensions/FHC-Core-International/Studiengangsleitung/loadBenotungen', data);
		} catch (error) {
			throw error;
		}
	}
};