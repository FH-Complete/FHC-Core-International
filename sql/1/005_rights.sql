INSERT INTO system.tbl_berechtigung(berechtigung_kurzbz, beschreibung)
SELECT 'extension/internationalReview', 'Um die Internationalisierungsmaßnahmen zu überprüfen'
WHERE
	NOT EXISTS(SELECT 1 FROM system.tbl_berechtigung WHERE berechtigung_kurzbz='extension/internationalReview');

INSERT INTO system.tbl_berechtigung(berechtigung_kurzbz, beschreibung)
SELECT 'extension/internationalStudent', 'Um die Internationalisierungsmaßnahmen zu planen als StudentIn'
WHERE
    NOT EXISTS(SELECT 1 FROM system.tbl_berechtigung WHERE berechtigung_kurzbz='extension/internationalStudent');

INSERT INTO system.tbl_berechtigung(berechtigung_kurzbz, beschreibung)
SELECT 'extension/internationalMassnahme', 'Um die Internationalisierungsmaßnahmen zu pflegen'
WHERE
    NOT EXISTS(SELECT 1 FROM system.tbl_berechtigung WHERE berechtigung_kurzbz='extension/internationalMassnahme');
