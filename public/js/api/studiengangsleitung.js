
export default {

	//nicht im API Controller - da nur JSON als return Wert
	getDownloadLink(massnahme)
	{
		return ('extensions/FHC-Core-International/Studiengangsleitung/download?massnahme=' + encodeURIComponent(massnahme));
	},
	getLoad(stg)
	{
		return {
			method: 'get',
			url: `extensions/FHC-Core-International/components/api/fronted/v1/Studiengangsleitung/load/${encodeURIComponent(stg)}`
		};
	},
	getStudents(data)
	{
		return {
			method: 'get',
			url: 'extensions/FHC-Core-International/components/api/fronted/v1/Studiengangsleitung/getStudents/',
			params: data
		};
	},

	getOrgForms(data)
	{
		return {
			method: 'get',
			url: `extensions/FHC-Core-International/components/api/fronted/v1/Studiengangsleitung/getOrgForms/${encodeURIComponent(data.stg)}/${encodeURIComponent(data.stsem)}`
		};
	},

	getLvs(data)
	{
		return {
			method: 'get',
			url: `extensions/FHC-Core-International/components/api/fronted/v1/Studiengangsleitung/getLVs/${encodeURIComponent(data.stg)}/${encodeURIComponent(data.stsem)}`
		};
	},

	loadBenotung(data)
	{
		return {
			method: 'get',
			url: `extensions/FHC-Core-International/components/api/fronted/v1/Studiengangsleitung/loadBenotungen/${encodeURIComponent(data.stg)}/${encodeURIComponent(data.stsem)}`,
		}
	},

	setStatus(data)
	{
		return {
			method: 'post',
			url: 'extensions/FHC-Core-International/components/api/fronted/v1/Studiengangsleitung/addStatus/',
			params: data
		}
	},
	setNote(data)
	{
		return {
			method: 'post',
			url: 'extensions/FHC-Core-International/components/api/fronted/v1/Studiengangsleitung/setNote/',
			params: data
		}
	},







};