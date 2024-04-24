<?php

class Massnahmen extends Auth_Controller
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
				'index' => self::BERECHTIGUNG_KURZBZ .':rw',
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
		$this->load->library('DmsLib');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahme_model', 'InternatmassnahmeModel');
		$this->_ci->load->model('extensions/FHC-Core-International/Internatmassnahmezuordnung_model', 'InternatmassnahmezuordnungModel');
		$this->_ci->load->model('system/Sprache_model', 'SpracheModel');

		$this->setControllerId(); // sets the controller id
		$this->_setAuthUID();
	}

	public function index()
	{
		$this->_ci->load->view('extensions/FHC-Core-International/massnahmen/massnahmen.php');
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
			aktiv'
		);

		$this->_ci->InternatmassnahmeModel->addOrder('aktiv, ects', 'DESC');
		$this->outputJson($this->_ci->InternatmassnahmeModel->load());
	}

	public function handleSave()
	{
		$postJson = $this->getPostJSON();
		if ($postJson->massnahme_id)
			return $this->updateMassnahme($postJson);
		else
			return $this->addMassnahme($postJson);
	}
	private function addMassnahme($postJson)
	{
		$bezeichnung = $postJson->bezeichnung;
		$bezeichnungeng = $postJson->bezeichnungeng;
		$ects = $postJson->ects;
		$beschreibung = $postJson->beschreibung;
		$beschreibungeng = $postJson->beschreibungeng;
		$aktiv = $postJson->aktiv;

		if (isEmptyString($bezeichnung) || isEmptyString($bezeichnungeng) || isEmptyString($ects)
			|| isEmptyString($beschreibung) || isEmptyString($beschreibungeng))
		{
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));
		}

		$bezeichnungmehrsprachig = "{". $bezeichnung. ", ". $bezeichnungeng . "}";
		$beschreibungmehrsprachig = "{". $beschreibung. ", ". $beschreibungeng . "}";

		$insert = $this->_ci->InternatmassnahmeModel->insert(array
			(
				'bezeichnung_mehrsprachig' => $bezeichnungmehrsprachig,
				'beschreibung_mehrsprachig' => $beschreibungmehrsprachig,
				'ects' => $ects,
				'aktiv' => $aktiv,
				'insertamum' => date('Y-m-d H:i:s'),
				'insertvon' => $this->_uid
			)
		);

		if (isError($insert))
			$this->terminateWithJsonError(getError($insert));

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
			aktiv'
		);

		$this->outputJsonSuccess($this->_ci->InternatmassnahmeModel->loadWhere(array('massnahme_id' =>  $insert->retval)));
	}

	public function updateMassnahme($postJson)
	{
		$massnahmeid = $postJson->massnahme_id;
		$bezeichnung = $postJson->bezeichnung;
		$bezeichnungeng = $postJson->bezeichnungeng;
		$ects = $postJson->ects;
		$beschreibung = $postJson->beschreibung;
		$beschreibungeng = $postJson->beschreibungeng;
		$aktiv = $postJson->aktiv;

		if (isEmptyString($bezeichnung) || isEmptyString($bezeichnungeng) || isEmptyString($ects)
			|| isEmptyString($beschreibung) || isEmptyString($beschreibungeng))
		{
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));
		}

		$bezeichnungmehrsprachig = "{". $bezeichnung. ", ". $bezeichnungeng . "}";
		$beschreibungmehrsprachig = "{". $beschreibung. ", ". $beschreibungeng . "}";

		$insert = $this->_ci->InternatmassnahmeModel->update(
			array('massnahme_id' => $massnahmeid),
			array
			(
				'bezeichnung_mehrsprachig' => $bezeichnungmehrsprachig,
				'beschreibung_mehrsprachig' => $beschreibungmehrsprachig,
				'ects' => $ects,
				'aktiv' => $aktiv,
				'updateamum' => date('Y-m-d H:i:s'),
				'updatevon' => $this->_uid
			)
		);


		if (isError($insert))
			$this->terminateWithJsonError(getError($insert));

		$this->outputJsonSuccess('Success');
	}

	public function deleteMassnahme()
	{

		$postJson = $this->getPostJSON();

		if (!isset($postJson->massnahme_id))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));

		$massnahmenID = $postJson->massnahme_id;

		if (isEmptyString((string)$massnahmenID))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));

		$massnahmenZuordnungen = $this->_ci->InternatmassnahmezuordnungModel->load(array('massnahme_id' => $massnahmenID));

		if (isError($massnahmenZuordnungen))
			$this->terminateWithJsonError(getError($massnahmenZuordnungen));

		if (hasData($massnahmenZuordnungen))
			$this->terminateWithJsonError('Die Massnahme wurde bereits zugewiesen und kann nicht gelöscht werden');

		$delete = $this->_ci->InternatmassnahmeModel->delete(array('massnahme_id' => $massnahmenID));

		if (isError($delete))
			$this->terminateWithJsonError(getError($delete));

		$this->outputJsonSuccess('Success');
	}

	private function _setAuthUID()
	{
		$this->_uid = getAuthUID();
		if (!$this->_uid) show_error('User authentification failed');
	}
}