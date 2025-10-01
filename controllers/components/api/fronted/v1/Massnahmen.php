<?php
class Massnahmen extends FHCAPI_Controller
{

	private $_ci; // Code igniter instance
	private $_uid;

	const BERECHTIGUNG_KURZBZ = 'extension/internationalMassnahme';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
				'handleSave' => self::BERECHTIGUNG_KURZBZ .':rw',
				'deleteMassnahme' => self::BERECHTIGUNG_KURZBZ .':rw',
				'load' => self::BERECHTIGUNG_KURZBZ .':rw',
			)
		);

		$this->_ci =& get_instance();
		$this->loadPhrases(
			array(
				'global',
				'ui',
				'international',
				'lehre'
			)
		);

		$this->load->library('WidgetLib');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahme_model', 'InternatmassnahmeModel');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahmezuordnung_model', 'InternatmassnahmezuordnungModel');
		$this->_ci->load->model('system/Sprache_model', 'SpracheModel');

		$this->setControllerId(); // sets the controller id
		$this->_setAuthUID();
	}
	public function load()
	{
		$this->_ci->SpracheModel->addSelect('index');
		$result = $this->_ci->SpracheModel->loadWhere(array('sprache' => getUserLanguage()));

		$language =  hasData($result) ? getData($result)[0]->index : 1;

		$this->_ci->InternatmassnahmeModel->addSelect(
			'massnahme_id, 
			bezeichnung_mehrsprachig[(' . $language . ')] as bezeichnungshow,
			beschreibung_mehrsprachig[(' . $language . ')] as beschreibungshow,
			bezeichnung_mehrsprachig[(1)] as bezeichnung,
			bezeichnung_mehrsprachig[(2)] as bezeichnungeng,
			beschreibung_mehrsprachig[(1)] as beschreibung,
			beschreibung_mehrsprachig[(2)] as beschreibungeng,
			ects,
			aktiv,
			einmalig'
		);

		$this->_ci->InternatmassnahmeModel->addOrder('aktiv, ects', 'DESC');
		$result = $this->_ci->InternatmassnahmeModel->load();
		$this->terminateWithSuccess(hasData($result) ? getData($result) : []);
	}

	public function handleSave()
	{
		$postJson = $this->_ci->input->post();
		if ($postJson['massnahme_id'])
			return $this->updateMassnahme($postJson);
		else
			return $this->addMassnahme($postJson);
	}
	private function addMassnahme($postJson)
	{
		$bezeichnung = $postJson['bezeichnung'];
		$bezeichnungeng = $postJson['bezeichnungeng'];
		$ects = $postJson['ects'];
		$beschreibung = $postJson['beschreibung'];
		$beschreibungeng = $postJson['beschreibungeng'];
		$aktiv = $postJson['aktiv'];
		$einmalig = $postJson['einmalig'];

		if (isEmptyString($bezeichnung) || isEmptyString($bezeichnungeng) || isEmptyString($ects)
			|| isEmptyString($beschreibung) || isEmptyString($beschreibungeng))
		{
			$this->terminateWithError($this->_ci->p->t('ui', 'felderFehlen'), self::ERROR_TYPE_GENERAL);
		}

		$bezeichnung = str_replace(",", "\,", $bezeichnung);
		$bezeichnungeng = str_replace(",", "\,", $bezeichnungeng);
		$bezeichnungmehrsprachig = "{". $bezeichnung. ", ". $bezeichnungeng . "}";

		$beschreibung = str_replace(",", "\,", $beschreibung);
		$beschreibungeng = str_replace(",", "\,", $beschreibungeng);
		$beschreibungmehrsprachig = "{". $beschreibung. ", ". $beschreibungeng . "}";

		$insert = $this->_ci->InternatmassnahmeModel->insert(array
			(
				'bezeichnung_mehrsprachig' => $bezeichnungmehrsprachig,
				'beschreibung_mehrsprachig' => $beschreibungmehrsprachig,
				'ects' => $ects,
				'aktiv' => !is_null($aktiv),
				'einmalig' => !is_null($einmalig),
				'insertamum' => date('Y-m-d H:i:s'),
				'insertvon' => $this->_uid
			)
		);

		if (isError($insert))
			$this->terminateWithError(getError($insert), self::ERROR_TYPE_GENERAL);

		$this->_ci->SpracheModel->addSelect('index');
		$result = $this->_ci->SpracheModel->loadWhere(array('sprache' => getUserLanguage()));

		$language =  hasData($result) ? getData($result)[0]->index : 1;

		$this->_ci->InternatmassnahmeModel->addSelect(
			'massnahme_id, 
			bezeichnung_mehrsprachig[(' . $language . ')] as bezeichnungshow,
			beschreibung_mehrsprachig[(' . $language . ')] as beschreibungshow,
			bezeichnung_mehrsprachig[(1)] as bezeichnung,
			bezeichnung_mehrsprachig[(2)] as bezeichnungeng,
			beschreibung_mehrsprachig[(1)] as beschreibung,
			beschreibung_mehrsprachig[(2)] as beschreibungeng,
			ects,
			aktiv,
			einmalig'
		);

		$result = $this->_ci->InternatmassnahmeModel->loadWhere(array('massnahme_id' =>  $insert->retval));
		$this->terminateWithSuccess(hasData($result) ? getData($result)[0] : []);
	}

	public function updateMassnahme($postJson)
	{
		$massnahmeid = $postJson['massnahme_id'];
		$bezeichnung = $postJson['bezeichnung'];
		$bezeichnungeng = $postJson['bezeichnungeng'];
		$ects = $postJson['ects'];
		$beschreibung = $postJson['beschreibung'];
		$beschreibungeng = $postJson['beschreibungeng'];
		$aktiv = $postJson['aktiv'];
		$einmalig = $postJson['einmalig'];

		if (isEmptyString($bezeichnung) || isEmptyString($bezeichnungeng) || isEmptyString($ects)
			|| isEmptyString($beschreibung) || isEmptyString($beschreibungeng))
		{
			$this->terminateWithError($this->_ci->p->t('ui', 'felderFehlen'), self::ERROR_TYPE_GENERAL);
		}

		$bezeichnung = str_replace(",", "\,", $bezeichnung);
		$bezeichnungeng = str_replace(",", "\,", $bezeichnungeng);
		$bezeichnungmehrsprachig = "{". $bezeichnung. ", ". $bezeichnungeng . "}";

		$beschreibung = str_replace(",", "\,", $beschreibung);
		$beschreibungeng = str_replace(",", "\,", $beschreibungeng);
		$beschreibungmehrsprachig = "{". $beschreibung. ", ". $beschreibungeng . "}";

		$insert = $this->_ci->InternatmassnahmeModel->update(
			array('massnahme_id' => $massnahmeid),
			array
			(
				'bezeichnung_mehrsprachig' => $bezeichnungmehrsprachig,
				'beschreibung_mehrsprachig' => $beschreibungmehrsprachig,
				'ects' => $ects,
				'aktiv' => $aktiv,
				'einmalig' => $einmalig,
				'updateamum' => date('Y-m-d H:i:s'),
				'updatevon' => $this->_uid
			)
		);


		if (isError($insert))
			$this->terminateWithError(getError($insert), self::ERROR_TYPE_GENERAL);

		$this->terminateWithSuccess('Success');
	}

	public function deleteMassnahme()
	{

		$massnahmenID = $this->_ci->input->post('massnahme_id');

		if (isEmptyString((string)$massnahmenID))
			$this->terminateWithError($this->_ci->p->t('ui', 'felderFehlen', self::ERROR_TYPE_GENERAL));

		$massnahmenZuordnungen = $this->_ci->InternatmassnahmezuordnungModel->load(array('massnahme_id' => $massnahmenID));

		if (isError($massnahmenZuordnungen))
			$this->terminateWithError(getError($massnahmenZuordnungen,  self::ERROR_TYPE_GENERAL));

		if (hasData($massnahmenZuordnungen))
			$this->terminateWithError('Die Massnahme wurde bereits zugewiesen und kann nicht gelöscht werden',  self::ERROR_TYPE_GENERAL);

		$delete = $this->_ci->InternatmassnahmeModel->delete(array('massnahme_id' => $massnahmenID));

		if (isError($delete))
			$this->terminateWithError(getError($delete),  self::ERROR_TYPE_GENERAL);

		$this->terminateWithSuccess('Success');
	}

	private function _setAuthUID()
	{
		$this->_uid = getAuthUID();
		if (!$this->_uid) show_error('User authentification failed');
	}
}