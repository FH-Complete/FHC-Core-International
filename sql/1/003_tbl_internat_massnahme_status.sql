CREATE TABLE IF NOT EXISTS extension.tbl_internat_massnahme_status
(
    massnahme_status_kurzbz             varchar(32) not null,
    bezeichnung_mehrsprachig            varchar(128)[]
);

DO $$
    BEGIN
        ALTER TABLE extension.tbl_internat_massnahme_status ADD CONSTRAINT tbl_internat_massnahme_status_pkey PRIMARY KEY (massnahme_status_kurzbz);
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

INSERT INTO extension.tbl_internat_massnahme_status(massnahme_status_kurzbz, bezeichnung_mehrsprachig)
SELECT 'planned', '{geplant, planned}'
    WHERE
        NOT EXISTS(SELECT 1 FROM extension.tbl_internat_massnahme_status WHERE massnahme_status_kurzbz='planned');

INSERT INTO extension.tbl_internat_massnahme_status(massnahme_status_kurzbz, bezeichnung_mehrsprachig)
SELECT 'accepted', '{akzeptiert,accepted}'
    WHERE
	NOT EXISTS(SELECT 1 FROM extension.tbl_internat_massnahme_status WHERE massnahme_status_kurzbz='accepted');

INSERT INTO extension.tbl_internat_massnahme_status(massnahme_status_kurzbz, bezeichnung_mehrsprachig)
SELECT 'performed', '{durchgeführt, performed}'
    WHERE
        NOT EXISTS(SELECT 1 FROM extension.tbl_internat_massnahme_status WHERE massnahme_status_kurzbz='performed');

INSERT INTO extension.tbl_internat_massnahme_status(massnahme_status_kurzbz, bezeichnung_mehrsprachig)
SELECT 'confirmed', '{bestätigt, confirmed}'
    WHERE
        NOT EXISTS(SELECT 1 FROM extension.tbl_internat_massnahme_status WHERE massnahme_status_kurzbz='confirmed');

INSERT INTO extension.tbl_internat_massnahme_status(massnahme_status_kurzbz, bezeichnung_mehrsprachig)
SELECT 'declined', '{abgelehnt, declined}'
    WHERE
        NOT EXISTS(SELECT 1 FROM extension.tbl_internat_massnahme_status WHERE massnahme_status_kurzbz='declined');

GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE extension.tbl_internat_massnahme_status TO vilesci;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE extension.tbl_internat_massnahme_status TO fhcomplete;
GRANT SELECT, UPDATE, INSERT ON TABLE extension.tbl_internat_massnahme_status TO web;