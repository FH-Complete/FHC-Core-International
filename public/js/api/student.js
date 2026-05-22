export default {

	getNachweis(massnahme)
	{
		return ('extensions/FHC-Core-International/Student/studentDownloadNachweis?massnahmenZuordnung=' + encodeURIComponent(massnahme));
	},

	getData()
	{
		return {
			method: 'get',
			url: 'extensions/FHC-Core-International/components/api/fronted/v1/Student/getData'
		};
	},

	uploadNachweis(data)
	{
		return {
			method: 'post',
			url: 'extensions/FHC-Core-International/components/api/fronted/v1/Student/studentAddNachweis',
			params: data,
			headers: {"Content-Type": "multipart/form-data"}
		};
	},

	deleteMassnahme(data)
	{
		return {
			method: 'post',
			url: 'extensions/FHC-Core-International/components/api/fronted/v1/Student/studentDeleteMassnahme',
			params: data,
		};
	},

	addMassnahme(data)
	{
		return {
			method: 'post',
			url: 'extensions/FHC-Core-International/components/api/fronted/v1/Student/studentAddMassnahme',
			params: data,
		};
	},

	deleteNachweis(data)
	{
		return {
			method: 'post',
			url: 'extensions/FHC-Core-International/components/api/fronted/v1/Student/studentDeleteNachweis',
			params: data,
		};
	},
};