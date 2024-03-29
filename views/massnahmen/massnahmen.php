<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'International-Massnahmen',
		'jquery3' => true,
		'jqueryui1' => true,
		'bootstrap3' => true,
		'fontawesome4' => true,
		'tablewidget' => true,
		'tabulator4' => true,
		'ajaxlib' => true,
		'dialoglib' => true,
		'navigationwidget' => true,
		'phrases' => array(
			'ui' => array(
				'global',
				'ui',
				'international',
				'lehre'
			)
		),
		'customJSs' => array(
			'public/extensions/FHC-Core-International/js/massnahmen.js',
			'public/js/bootstrapper.js',
		)
	)
);
?>
<body>
<div id="wrapper">
	<?php echo $this->widgetlib->widget('NavigationWidget'); ?>
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
				<?php $this->load->view('extensions/FHC-Core-International/massnahmen/massnahmenData.php'); ?>
			</div>
			<div>
				<?php $this->load->view('extensions/FHC-Core-International/massnahmen/massnahmenAdd.php'); ?>
			</div>
		</div>
	</div>
</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>

