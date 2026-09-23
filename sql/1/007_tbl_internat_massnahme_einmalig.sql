DO $$
BEGIN
ALTER TABLE extension.tbl_internat_massnahme ADD COLUMN einmalig BOOLEAN DEFAULT false;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;