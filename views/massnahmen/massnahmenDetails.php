<?php
	$this->load->view(
		'templates/FHC-Header',
		array(
			'title' => 'Massnahme Details',
			'jquery3' => true,
			'bootstrap3' => true,
			'fontawesome4' => true,
			'jqueryui1' => true,
			'ajaxlib' => true,
			'tablesorter2' => true,
			'sbadmintemplate3' => true,
			'dialoglib' => true,
			'addons' => false,
			'navigationwidget' => true,
			'customJSs' => array(
				'public/js/bootstrapper.js',
				'public/extensions/FHC-Core-International/js/massnahmenDetails.js',
			),
			'phrases' => array(
				'ui',
				'global'
			)
		)
	);
?>
<body>
<div id="wrapper">
	<?php echo $this->widgetlib->widget('NavigationWidget'); ?>
	<div id="page-wrapper">
		<div class="container-fluid">
			<input type="hidden" id="hiddenmassnahmenid" value="<?php echo $massnahme->massnahme_id ?>">
			<div class="row">
				<div class="col-lg-12">
					<h3 class="page-header">
						<?php echo $this->p->t('international', 'massnahmeBearbeiten'); ?>
					</h3>
				</div>
			</div>
			<br/>
			<div class="form-group row">
				<div class="col-sm-4">
					<label for="bezeichnung"><?php echo $this->p->t('international', 'bezeichnung'); ?></label>
					<input type="text" class="form-control" id="bezeichnung" value="<?php echo $massnahme->bezeichnung_mehrsprachig[0]?>">
				</div>
				<div class="col-sm-4">
					<label for="bezeichnungeng"><?php echo $this->p->t('international', 'bezeichnungeng'); ?></label>
					<input type="text" class="form-control" id="bezeichnungeng" value="<?php echo $massnahme->bezeichnung_mehrsprachig[1]?>">
				</div>
				<div class="col-sm-1">
					<label for="ects"><?php echo $this->p->t('lehre', 'ects'); ?></label>
					<input type="number" class="form-control" id="ects" value="<?php echo $massnahme->ects ?>">
				</div>
				<div class="col-sm-1 text-center">
					<label for="speichern"><?php echo $this->p->t('ui', 'speichern'); ?></label>
					<button class="btn btn-default form-control" id="update">
						<i class="fa fa-floppy-o fa-fw fa-1x" aria-hidden="true"></i>
					</button>
				</div>
				<div class="col-sm-1 text-center">
					<label for="loeschen"><?php echo $this->p->t('ui', 'loeschen'); ?></label>
					<button class="btn btn-default form-control" id="loeschen">
						<i class="fa fa-trash fa-fw fa-1x" aria-hidden="true"></i>
					</button>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-sm-4">
					<label for="beschreibung"><?php echo $this->p->t('international', 'beschreibung'); ?></label>
					<textarea class="form-control" id="beschreibung" rows="5"><?php echo $massnahme->beschreibung_mehrsprachig[0]?></textarea>
				</div>
				<div class="col-sm-4">
					<label for="beschreibungeng"><?php echo $this->p->t('international', 'beschreibungeng'); ?></label>
					<textarea class="form-control" id="beschreibungeng" rows="5"><?php echo $massnahme->beschreibung_mehrsprachig[1]?></textarea>
				</div>
				<div class="col-sm-1">
					<label for="aktiv"><?php echo $this->p->t('global', 'aktiv'); ?></label>
					<input type="checkbox" class="checkbox" id="aktiv" <?php echo ($massnahme->aktiv) ? 'checked' : ''?>>
				</div>

			</div>
		</div> <!-- ./container-fluid-->
	</div> <!-- ./page-wrapper-->
</div> <!-- ./wrapper -->
</div> <!-- ./wrapper -->
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>
