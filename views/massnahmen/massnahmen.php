<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'International-Massnahmen',
		'vue3' => true,
		'bootstrap5' => true,
		'primevue3' => true,
		'tabulator5' => true,
		'fontawesome6' => true,
		'axios027' => true,
		'phrases' => array(
			'international',
		),
		'customJSModules' => array(
			'public/extensions/FHC-Core-International/js/apps/MassnahmenApp.js',
		)
	)
);
?>
<body>
<div id="main">
	<massnahmen></massnahmen>
</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>
