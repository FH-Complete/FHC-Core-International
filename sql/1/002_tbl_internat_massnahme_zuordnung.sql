CREATE SEQUENCE IF NOT EXISTS extension.tbl_internat_massnahme_zuordnung_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

GRANT SELECT, UPDATE ON SEQUENCE extension.tbl_internat_massnahme_zuordnung_id_seq TO vilesci;
GRANT SELECT, UPDATE ON SEQUENCE extension.tbl_internat_massnahme_zuordnung_id_seq TO fhcomplete;
GRANT SELECT, UPDATE ON SEQUENCE extension.tbl_internat_massnahme_zuordnung_id_seq TO web;

CREATE TABLE IF NOT EXISTS extension.tbl_internat_massnahme_zuordnung
(
    massnahme_zuordnung_id              integer not null default NEXTVAL('extension.tbl_internat_massnahme_zuordnung_id_seq'::regclass),
    prestudent_id                       integer,
    massnahme_id                        bigint not null,
    anmerkung                           text,
    studiensemester_kurzbz              varchar(32),
    dms_id                              integer,
    insertamum                          timestamp without time zone default now(),
    insertvon                           varchar(32),
    updateamum                          timestamp without time zone,
    updatevon                            varchar(32)
);

DO $$
    BEGIN
        ALTER TABLE extension.tbl_internat_massnahme_zuordnung ADD CONSTRAINT tbl_internat_massnahme_zuordnung_pkey PRIMARY KEY (massnahme_zuordnung_id);
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
    BEGIN
        ALTER TABLE extension.tbl_internat_massnahme_zuordnung ADD CONSTRAINT tbl_internat_massnahme_zuordnung_prestudent_id_fkey FOREIGN KEY (prestudent_id) REFERENCES public.tbl_prestudent(prestudent_id) ON UPDATE CASCADE ON DELETE RESTRICT;
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
    BEGIN
        ALTER TABLE extension.tbl_internat_massnahme_zuordnung ADD CONSTRAINT tbl_internat_massnahme_zuordnung_massnahme_id_fkey FOREIGN KEY (massnahme_id) REFERENCES extension.tbl_internat_massnahme(massnahme_id) ON UPDATE CASCADE ON DELETE RESTRICT;
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
    BEGIN
        ALTER TABLE extension.tbl_internat_massnahme_zuordnung ADD CONSTRAINT tbl_internat_massnahme_zuordnung_studiensemester_kurzbz_fkey FOREIGN KEY (studiensemester_kurzbz) REFERENCES public.tbl_studiensemester(studiensemester_kurzbz) ON UPDATE CASCADE ON DELETE RESTRICT;
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

DO $$
    BEGIN
        ALTER TABLE extension.tbl_internat_massnahme_zuordnung ADD CONSTRAINT tbl_internat_massnahme_zuordnung_dms_id_fkey FOREIGN KEY (dms_id) REFERENCES campus.tbl_dms(dms_id) ON UPDATE CASCADE ON DELETE RESTRICT;
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE extension.tbl_internat_massnahme_zuordnung TO vilesci;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE extension.tbl_internat_massnahme_zuordnung TO fhcomplete;
GRANT SELECT, UPDATE, INSERT ON TABLE extension.tbl_internat_massnahme_zuordnung TO web;