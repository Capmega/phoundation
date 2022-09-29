<?php
/*
 * Add seo columns to twilio tables
 */
sql_column_exists('twilio_accounts', 'seoemail', '!ALTER TABLE `twilio_accounts` ADD COLUMN `seoemail` VARCHAR(128) NULL DEFAULT NULL AFTER `email`');
sql_column_exists('twilio_numbers' , 'seoname' , '!ALTER TABLE `twilio_numbers`  ADD COLUMN `seoname`  VARCHAR(64)  NULL DEFAULT NULL AFTER `name`');
sql_column_exists('twilio_groups'  , 'seoname' , '!ALTER TABLE `twilio_groups`   ADD COLUMN `seoname`  VARCHAR(64)  NULL DEFAULT NULL AFTER `name`');

sql_index_exists('twilio_accounts', 'seoemail', '!ALTER TABLE `twilio_accounts` ADD UNIQUE KEY `seoemail` (`seoemail`)');
sql_index_exists('twilio_numbers' , 'seoname' , '!ALTER TABLE `twilio_numbers`  ADD UNIQUE KEY `seoname`  (`seoname`)');
sql_index_exists('twilio_groups'  , 'seoname' , '!ALTER TABLE `twilio_groups`   ADD UNIQUE KEY `seoname`  (`seoname`)');

/*
 * Update the seo* columns to have real values
 */
load_libs('seo');

$tables = array('twilio_accounts' => 'email',
                'twilio_numbers'  => 'name',
                'twilio_groups'   => 'name');

foreach($tables as $table => $column) {
    $entries = sql_query('SELECT `id`, `'.$column.'` FROM `'.$table.'`');
    $update  = sql_prepare('UPDATE `'.$table.'` SET  `seo'.$column.'` = :seo WHERE `id` = :id');

    while($entry = sql_fetch($entries)) {
        $seo = seo_unique($entry[$column], $table, $entry['id'], 'seo'.$column);

        $update->execute(array(':id'  => $entry['id'],
                               ':seo' => $seo));
    }

    cli_dot();
}
?>