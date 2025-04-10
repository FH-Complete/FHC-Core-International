import {CoreRESTClient} from "../../../../../public/js/RESTClient.js";

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
	},
	getLvs(data)
	{
		try {
			return CoreRESTClient.get('extensions/FHC-Core-International/Studiengangsleitung/getLVs', data);
		} catch (error) {
			throw error;
		}
	},
	getOrgForms(data)
	{
		try {
			return CoreRESTClient.get('extensions/FHC-Core-International/Studiengangsleitung/getOrgForms', data);
		} catch (error) {
			throw error;
		}
	}
};
