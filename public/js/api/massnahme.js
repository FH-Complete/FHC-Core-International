
export default {
	getLoad()
	{
		return {
			method: 'get',
			url: 'extensions/FHC-Core-International/components/api/fronted/v1/Massnahmen/load/'
		};
	},

	handleSave(data)
	{
		return {
			method: 'post',
			url: 'extensions/FHC-Core-International/components/api/fronted/v1/Massnahmen/handleSave/',
			params: data
		};
	},

	deleteMassnahme(data)
	{
		return {
			method: 'post',
			url: 'extensions/FHC-Core-International/components/api/fronted/v1/Massnahmen/deleteMassnahme/',
			params: data
		};
	},

};