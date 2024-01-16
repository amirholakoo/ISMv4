# ISMv4

sudo chown -R admin:www-data /var/www/html

sudo chmod -R 775 qrcodes/

We need to add Supplier name into Raw Materials table. FK?

We can description to Raw Materials

ALTER TABLE RawMaterials ADD SupplierName VARCHAR(255);

ALTER TABLE RawMaterials ADD description VARCHAR(255);

UPDATE RawMaterials rm

INNER JOIN Suppliers s ON rm.SupplierID = s.SupplierID

SET rm.SupplierName = s.SupplierName;
