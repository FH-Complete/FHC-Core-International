<div class="row text-right">
	<div class="col-xs-12">
		<button value="Hinzufügen" class="btn btn-default hinzufuegen">
			<i class="fa fa-plus fa-fw fa-1x" aria-hidden="true"></i>
		</button>
	</div>
</div>

<div class="massnahmenAdd" style="display:none">
	<div class="form-group row">
		<div class="col-sm-5">
			<label for="bezeichnung"><?php echo $this->p->t('international', 'bezeichnung'); ?></label>
			<input type="text" class="form-control" id="bezeichnung">
		</div>
		<div class="col-sm-5">
			<label for="bezeichnungeng"><?php echo $this->p->t('international', 'bezeichnungeng'); ?></label>
			<input type="text" class="form-control" id="bezeichnungeng">
		</div>
		<div class="col-sm-1">
			<label for="ects"><?php echo $this->p->t('lehre', 'ects'); ?></label>
			<input type="number" class="form-control" id="ects">
		</div>
		<div class="col-sm-1 text-center">
			<label for="speichern"><?php echo $this->p->t('ui', 'speichern'); ?></label>
			<button class="btn btn-default form-control" id="speichern">
				<i class="fa fa-floppy-o fa-fw fa-1x" aria-hidden="true"></i>
			</button>
		</div>
	</div>

	<div class="form-group row">
		<div class="col-sm-5">
			<label for="beschreibung"><?php echo $this->p->t('international', 'beschreibung'); ?></label>
			<textarea class="form-control" id="beschreibung" rows="5"></textarea>
		</div>
		<div class="col-sm-5">
			<label for="beschreibungeng"><?php echo $this->p->t('international', 'beschreibungeng'); ?></label>
			<textarea class="form-control" id="beschreibungeng" rows="5"></textarea>
		</div>
		<div class="col-sm-1">
			<label for="aktiv"><?php echo $this->p->t('global', 'aktiv'); ?></label>
			<input type="checkbox" class="checkbox" id="aktiv">
		</div>
	</div>
</div>