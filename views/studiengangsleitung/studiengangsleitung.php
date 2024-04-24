<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'International-Studiengangsleitung',
		'vue3' => true,
		'bootstrap5' => true,
		'primevue3' => true,
		'tabulator5' => true,
		'fontawesome6' => true,
		'axios027' => true,
		'phrases' => array(
			'international',
		),
		'customCSSs' => array(
			'public/extensions/FHC-Core-International/css/studiengangsleitung.css'
		),
		'customJSModules' => array(
			'public/extensions/FHC-Core-International/js/apps/StudiengangsleitungApp.js',
		)
	)
);
?>
<body>
<div id="main">

	<studiengangsleitung
			:stgs="<?= htmlspecialchars(json_encode($studiengaenge)); ?>"
			:lvs="<?= htmlspecialchars(json_encode($lehrveranstaltungen)); ?>"
			:stsems="<?= htmlspecialchars(json_encode($studiensemester)); ?>"
			:aktstsem="<?= htmlspecialchars(json_encode($aktstsem)); ?>"
			:readonly="<?= htmlspecialchars(json_encode($readOnly)); ?>"
	></studiengangsleitung>
</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>

