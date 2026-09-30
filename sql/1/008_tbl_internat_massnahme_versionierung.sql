DO $$
BEGIN
    ALTER TABLE extension.tbl_internat_massnahme ADD COLUMN gueltig_von DATE;
    ALTER TABLE extension.tbl_internat_massnahme ADD COLUMN gueltig_bis DATE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;