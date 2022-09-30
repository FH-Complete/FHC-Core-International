CREATE SEQUENCE IF NOT EXISTS extension.tbl_internat_massnahme_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

GRANT SELECT, UPDATE ON SEQUENCE extension.tbl_internat_massnahme_id_seq TO vilesci;
GRANT SELECT, UPDATE ON SEQUENCE extension.tbl_internat_massnahme_id_seq TO fhcomplete;
GRANT SELECT, UPDATE ON SEQUENCE extension.tbl_internat_massnahme_id_seq TO web;

CREATE TABLE IF NOT EXISTS extension.tbl_internat_massnahme
(
    massnahme_id                        integer not null default NEXTVAL('extension.tbl_internat_massnahme_id_seq'::regclass),
    bezeichnung_mehrsprachig            varchar(256)[],
    beschreibung_mehrsprachig           text[],
    ects                                numeric(5,2),
    aktiv                               boolean default true not null,
    insertamum                          timestamp without time zone default now(),
    insertvon                            varchar(32),
    updateamum                          timestamp without time zone,
    updatevon                            varchar(32)
);

DO $$
    BEGIN
        ALTER TABLE extension.tbl_internat_massnahme ADD CONSTRAINT tbl_internat_massnahme_pkey PRIMARY KEY (massnahme_id);
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE extension.tbl_internat_massnahme TO vilesci;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE extension.tbl_internat_massnahme TO fhcomplete;
GRANT SELECT, UPDATE, INSERT ON TABLE extension.tbl_internat_massnahme TO web;