<?php
$includesArray = array(
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
);

if(defined("CIS4") && CIS4)
{
	$this->load->view(
		'templates/CISVUE-Header',
		$includesArray
	);
}
else
{
	$this->load->view(
		'templates/FHC-Header',
		$includesArray
	);
}
?>
<body>
<div id="main">
	<student
			:massnahmen="<?= htmlspecialchars(json_encode($massnahmen)); ?>"
			:studiensemester="<?= htmlspecialchars(json_encode($studiensemester)); ?>"
	></student>
</div>

<?php
if (defined("CIS4") && CIS4) {
	$this->load->view(
		'templates/CISVUE-Footer',
		$includesArray
	);
} else {
	$this->load->view(
		'templates/FHC-Footer', 
		$includesArray
	);
}
?>


