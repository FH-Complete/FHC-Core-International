DO $$
    BEGIN
        ALTER TABLE extension.tbl_internat_massnahme_zuordnung ADD COLUMN anmerkung_stgl text DEFAULT NULL;
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;