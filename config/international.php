<?php

//Studiengaenge die keinen Zugriff haben sollen
$config['stg_kz_blacklist'] = array();

//Studiengaenge AusnahmeListe, dass die Massnahmen für den Studiengang nicht gewählt werden kann
//array(stg_kz => array(massnahmen_ids))
$config['stg_massnahmen_blacklist'] = array(0 => array(0));