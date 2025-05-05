CREATE SEQUENCE IF NOT EXISTS extension.tbl_internat_massnahme_zuordnung_status_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

GRANT SELECT, UPDATE ON SEQUENCE extension.tbl_internat_massnahme_zuordnung_status_id_seq TO vilesci;
GRANT SELECT, UPDATE ON SEQUENCE extension.tbl_internat_massnahme_zuordnung_status_id_seq TO fhcomplete;
GRANT SELECT, UPDATE ON SEQUENCE extension.tbl_internat_massnahme_zuordnung_status_id_seq TO web;

CREATE TABLE IF NOT EXISTS extension.tbl_internat_massnahme_zuordnung_status
(
    massnahme_zuordnung_status_id       integer not null default NEXTVAL('extension.tbl_internat_massnahme_zuordnung_status_id_seq'::regclass),
    massnahme_zuordnung_id              integer not null,
    datum                               timestamp without time zone not null,
    massnahme_status_kurzbz             varchar(32) not null,
    insertamum                          timestamp without time zone default now(),
    insertvon                           varchar(32)
);

DO $$
    BEGIN
        ALTER TABLE extension.tbl_internat_massnahme_zuordnung_status ADD CONSTRAINT tbl_internat_massnahme_zuordnung_status_pkey PRIMARY KEY (massnahme_zuordnung_status_id);
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
    BEGIN
        ALTER TABLE extension.tbl_internat_massnahme_zuordnung_status ADD CONSTRAINT tbl_internat_massnahme_zuordnung_status_zuordnung_id_fkey FOREIGN KEY (massnahme_zuordnung_id) REFERENCES extension.tbl_internat_massnahme_zuordnung(massnahme_zuordnung_id) ON UPDATE CASCADE ON DELETE RESTRICT;
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
    BEGIN
        ALTER TABLE extension.tbl_internat_massnahme_zuordnung_status ADD CONSTRAINT tbl_internat_massnahme_zuordnung_massnahme_status_kurzbz_fkey FOREIGN KEY (massnahme_status_kurzbz) REFERENCES extension.tbl_internat_massnahme_status(massnahme_status_kurzbz) ON UPDATE CASCADE ON DELETE RESTRICT;
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
    BEGIN
        CREATE INDEX idx_status_massnahme_zuordnung_id ON extension.tbl_internat_massnahme_zuordnung_status USING btree (massnahme_zuordnung_id);
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE extension.tbl_internat_massnahme_zuordnung_status TO vilesci;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE extension.tbl_internat_massnahme_zuordnung_status TO fhcomplete;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE extension.tbl_internat_massnahme_zuordnung_status TO web;