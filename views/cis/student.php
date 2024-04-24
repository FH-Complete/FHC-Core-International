<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'International-Student',
		'vue3' => true,
		'bootstrap5' => true,
		'primevue3' => true,
		'tabulator5' => true,
		'fontawesome6' => true,
		'axios027' => true,
		'phrases' => array(
			'ui',
			'international'
		),
		'customCSSs' => array(
			'public/extensions/FHC-Core-International/css/student.css'
		),
		'customJSModules' => array(
			'public/extensions/FHC-Core-International/js/apps/StudentApp.js',
		),
	)
);
?>
<body>
<div id="main">
	<student
			:massnahmen="<?= htmlspecialchars(json_encode($massnahmen)); ?>"
			:studiensemester="<?= htmlspecialchars(json_encode($studiensemester)); ?>"
	></student>
</div>

<?php $this->load->view('templates/FHC-Footer'); ?>


