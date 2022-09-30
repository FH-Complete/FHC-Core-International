<div class="massnahmenAdd">
	<h4 class="page-header">
		<?php echo $this->p->t('international', 'addMassnahme'); ?>
	</h4>

	<div class="row col-sm-12">
		<div class="col-sm-6">
			<div class="form-group">
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
			</div>
		</div>
		<div class="col-sm-6">

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

	</div>
	<div class="row col-sm-12">
		<div class="col-sm-6">
			<div class="form-group">
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
		</div>
	</div>
	<div class="row col-sm-12">
		<div class="col-sm-6">
			<div class="form-group">
				<?php echo form_textarea(array(
					'id' => 'massnahmenAnmerkung',
					'placeholder' => $this->p->t('global', 'anmerkung'),
					'rows' => 3
				)); ?>
			</div>
		</div>
	</div>
	<div class="row col-sm-12">
		<div class="col-sm-6 text-right">
			<div class="form-group">
				<button class="btn btn-default " id="addStudentMassnahme">
					<?php echo $this->p->t('ui', 'hinzufuegen'); ?>
				</button>
			</div>
		</div>
	</div>
</div>




