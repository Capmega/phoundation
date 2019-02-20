<?php
/*
 * Add support for barcode device scanners
 */
sql_query('ALTER TABLE `devices` MODIFY COLUMN `type` ENUM("fingerprint-reader", "document-scanner", "barcode-scanner", "webcam") NULL DEFAULT NULL');
?>