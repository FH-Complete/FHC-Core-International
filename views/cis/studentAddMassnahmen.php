<div class="row text-right">
	<div class="col-xs-12">
		<button value="Hinzufügen" class="btn btn-default hinzufuegen">
			<i class="fa fa-plus fa-fw fa-1x" aria-hidden="true"></i>
		</button>
	</div>
</div>

<div class="massnahmenAdd" style="display:none">
	<div class="form-group row">
		<label for="massnahmenSelect" class="col-sm-5 col-form-label"><?php echo $this->p->t('international', 'massnahmen'); ?></label>
		<label for="studiensemesterSelect" class="col-sm-2 col-form-label"><?php echo $this->p->t('lehre', 'studiensemester'); ?></label>
		<label for="massnahmenAnmerkung" class="col-sm-3 col-form-label"><?php echo $this->p->t('global', 'anmerkung'); ?></label>
		<label for="addStudentMassnahme" class="col-sm-1 col-form-label text-center"><?php echo $this->p->t('ui', 'hinzufuegen'); ?></label>
	</div>
	<div class="form-group row">
		<div class="col-sm-5">
			<select class="form-control" id="massnahmenSelect" required>
				<option value="null" disabled selected><?php echo $this->p->t('international', 'massnahmen'); ?></option>
				<?php
				if(is_array($massnahmen))
				{
					foreach ($massnahmen as $massnahme)
					{
						echo '<option value="' . $massnahme->massnahme_id. '"> ('. $massnahme->ects .') '. $massnahme->bezeichnung .'</option>';
					}
				}
				?>
			</select>
			<br />
			<?php
			if(is_array($massnahmen))
			{
				foreach ($massnahmen as $massnahme)
				{
					echo '<div class="alert alert-info beschreibung" role="alert" style="display:none" id="beschreibung_' . $massnahme->massnahme_id . '">' . $massnahme->beschreibung . '</div>';
				}
			}
			?>
		</div>
		<div class="col-sm-2">
			<select class="form-control" id="studiensemesterSelect" required>
				<option value="null" disabled selected><?php echo $this->p->t('lehre', 'studiensemester'); ?></option>
				<?php
				foreach ($studiensemester as $semester)
				{
					echo '<option value="' . $semester->studiensemester_kurzbz. '">'. $semester->studiensemester_kurzbz .'</option>';
				}
				?>
			</select>
		</div>
		<div class="col-sm-3">
			<?php echo form_textarea(array(
				'id' => 'massnahmenAnmerkung',
				'placeholder' => $this->p->t('global', 'anmerkung'),
				'rows' => 3
			)); ?>
		</div>
		<div class="col-sm-1 text-center">
			<button class="btn btn-default" id="addStudentMassnahme" title="<?php echo $this->p->t('ui', 'hinzufuegen'); ?>">
				<i class="fa fa-floppy-o fa-fw fa-1x" aria-hidden="true"></i>
			</button>
		</div>
	</div>
	<div class="form-group row infotext">
		<div class="col-sm-5">

		</div>
	</div>
</div>




