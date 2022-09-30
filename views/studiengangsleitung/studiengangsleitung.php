<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'International-Studiengangsleitung',
		'jquery' => true,
		'jqueryui' => true,
		'bootstrap' => true,
		'fontawesome' => true,
		'tablewidget' => true,
		'tabulator' => true,
		'ajaxlib' => true,
		'dialoglib' => true,
		'phrases' => array(
			'global',
			'ui',
			'international'
		),
		'customCSSs' => array(
			'public/extensions/FHC-Core-International/css/studiengangsleitung.css'
		),
		'customJSs' => array(
			'public/extensions/FHC-Core-International/js/studiengangsleitung.js',
			'public/js/bootstrapper.js',
		)
	)
);
?>
<body>
<div id="wrapper">
	<div id="page-wrapper">
		<div class="container-fluid">
			<div class="row">
				<div class="col-lg-12">
					<h3 class="page-header">
						<?php echo $this->p->t('international', 'massnahmen'); ?>
					</h3>
				</div>
			</div>

			<div>
				<?php $this->load->view('extensions/FHC-Core-International/studiengangsleitung/studiengangsleitungData.php'); ?>
			</div>
			<div class="col-xs-12">
				<div class="row text-left">
					<button id="acceptAll" class="btn btn-default" type="button"><?php echo $this->p->t('international', 'alleAkzeptierenPlan'); ?></button>
				</div>
				<br />
				<div class="row text-left">
					<div>
						<div class="btn-group" role="group">
							<button id="plannedMore" class="btn btn-default" type="button" title="<?php echo $this->p->t('international', 'mehrverplant'); ?>"><i class='fa fa-calendar-plus-o'></i></button>
							<button id="plannedLess" class="btn btn-default" type="button" title="<?php echo $this->p->t('international', 'wenigerverplant'); ?>"><i class='fa fa-calendar-minus-o'></i></button>
							<button id="confirmedMore" class="btn btn-default" type="button" title="<?php echo $this->p->t('international', 'mehrbestaetigt'); ?>"><i class='fa fa-calendar-check-o'></i></button>
							<button id="confirmedLess" class="btn btn-default" type="button" title="<?php echo $this->p->t('international', 'wenigerbestaetigt'); ?>"><i class='fa fa-calendar-times-o'></i></button>

							<button id="showOpen" class="btn btn-default" type="button" title="<?php echo $this->p->t('international', 'allegeplanten'); ?>"><i class='fa fa-calendar'></i></button>
							<button id="currentOpenSemester" class="btn btn-default" type="button" title="<?php echo $this->p->t('international', 'alleMassnahmenJetzt'); ?>"><i class='fa fa-clock-o'></i></button>
							<button id="currentSemester" class="btn btn-default" type="button" title="<?php echo $this->p->t('international', 'alleStudierendeJetzt'); ?>"><i class='fa fa-calendar-o'></i></button>
							<button id="lastSemester" class="btn btn-default" type="button" title="<?php echo $this->p->t('international', 'lastSemester'); ?>"><i class='fa fa-clock-o'></i></button>
							<button id="deleteFilter" class="btn btn-default" type="button" title="<?php echo $this->p->t('ui', 'alleAnzeigen'); ?>"><i class='fa fa-users'></i></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>

