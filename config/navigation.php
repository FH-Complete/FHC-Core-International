<?php
// Add Menu-Entry to Main Page
$config['navigation_header']['*']['Lehre']['children'] = array(
	'studiengangleitung' => array(
		'link' => site_url('extensions/FHC-Core-International/Studiengangsleitung'),
		'description' => 'Internationalisierung - Studiengangsleitung',
		'expand' => false,
		'requiredPermissions' => 'extension/internationalReview:rw'
	),
	'massnahmen' => array(
		'link' => site_url('extensions/FHC-Core-International/Massnahmen'),
		'description' => 'Internationalisierung - Massnahmen',
		'expand' => false,
		'requiredPermissions' => 'extension/internationalMassnahme:rw'
	)
);
