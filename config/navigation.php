<?php
// Add Menu-Entry to Main Page
$config['navigation_header']['*']['Lehre']['children']['international'] = array(
	'link' => site_url('extensions/FHC-Core-International/studiengangsleitung'),
	'description' => 'Internationalisierung - Studiengangsleitung',
	'expand' => false,
	'requiredPermissions' => 'extension/internationalReview:rw'
);

$config['navigation_header']['*']['Lehre']['children']['internationalmassnahme'] = array(
	'link' => site_url('extensions/FHC-Core-International/massnahmen'),
	'description' => 'Internationalisierung - Massnahmen',
	'expand' => false,
	'requiredPermissions' => 'extension/internationalMassnahme:rw'
);
