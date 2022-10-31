<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'International-Student',
		'jquery3' => true,
		'jqueryui1' => true,
		'bootstrap3' => true,
		'fontawesome4' => true,
		'tablewidget' => true,
		'tabulator4' => true,
		'ajaxlib' => true,
		'dialoglib' => true,
		'phrases' => array(
			'ui',
			'international'
		),
		'customJSs' => array(
			'public/extensions/FHC-Core-International/js/student.js',
			'public/js/bootstrapper.js',
		),
		'customCSSs' => array(
			'public/extensions/FHC-Core-International/css/student.css'
		),
	)
);
?>
<body>
	<div id="wrapper">
		<div id="page-wrapper">
			<div class="container-fluid">
				<div class="row">
					<div class="col-xs-12">
						<h3 class="page-header">
							<?php echo $this->p->t('international', 'internationalskills'); ?>
							<i class="fa fa-info-circle showInfoText text-right"></i>
						</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-6">
						<div class="alert alert-info internationalskills" style="display:none">
							<?php echo $this->p->t('international', 'internationalbeschreibung'); ?>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12">
						<?php
						$this->load->view('extensions/FHC-Core-International/cis/studentData.php');
						?>
					</div>
				</div>

				<div>
					<?php
					$this->load->view('extensions/FHC-Core-International/cis/studentAddMassnahmen.php');
					?>
				</div>
			</div>
		</div>
	</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>


