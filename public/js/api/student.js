import {CoreRESTClient} from "../../../../../public/js/RESTClient.js";

export default {

	deleteMassnahme(data)
	{
		try {
			return CoreRESTClient.post('/extensions/FHC-Core-International/Student/studentDeleteMassnahme', data);
		} catch (error) {
			throw error;
		}
	},
	getData()
	{
		try {
			return CoreRESTClient.get('/extensions/FHC-Core-International/Student/getData');
		} catch (error) {
			throw error;
		}
	},
	addMassnahme(data)
	{
		try {
			return CoreRESTClient.post('/extensions/FHC-Core-International/Student/studentAddMassnahme', data);
		} catch (error) {
			throw error;
		}
	},
	uploadNachweis(data)
	{
		try {
			return CoreRESTClient.post('/extensions/FHC-Core-International/Student/studentAddNachweis', data, {Headers: { "Content-Type": "multipart/form-data" }});
		} catch (error) {
			throw error;
		}
	},
	deleteNachweis(data)
	{
		try {
			return CoreRESTClient.post('/extensions/FHC-Core-International/Student/studentDeleteNachweis', data);
		} catch (error) {
			throw error;
		}
	}
};
