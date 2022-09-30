<?php
/*
 * Implement meta data support for rights and roles tables
 * Fix missing meta links for servers table
 */
sql_column_exists('rights', 'meta_id', '!ALTER TABLE `rights` ADD COLUMN `meta_id` INT(11) AFTER `createdby`');
sql_column_exists('roles' , 'meta_id', '!ALTER TABLE `roles`  ADD COLUMN `meta_id` INT(11) AFTER `createdby`');

sql_index_exists('rights', 'meta_id', '!ALTER TABLE `rights` ADD KEY `meta_id` (`meta_id`)');
sql_index_exists('roles' , 'meta_id', '!ALTER TABLE `roles`  ADD KEY `meta_id` (`meta_id`)');

sql_foreignkey_exists('rights', 'fk_rights_meta_id', '!ALTER TABLE `rights` ADD CONSTRAINT `fk_rights_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT');
sql_foreignkey_exists('roles' , 'fk_roles_meta_id', '!ALTER TABLE `roles`  ADD CONSTRAINT `fk_roles_meta_id`  FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT');

/*
 * Remove modifiedon/modifiedby
 */
sql_foreignkey_exists('rights', 'fk_rights_modifiedby', 'ALTER TABLE `rights` DROP FOREIGN KEY `fk_rights_modifiedby`');
sql_foreignkey_exists('roles' , 'fk_roles_modifiedby' , 'ALTER TABLE `roles`  DROP FOREIGN KEY `fk_roles_modifiedby`');

sql_index_exists('rights', 'modifiedon', 'ALTER TABLE `rights` DROP INDEX `modifiedon`');
sql_index_exists('roles' , 'modifiedon', 'ALTER TABLE `roles`  DROP INDEX `modifiedon`');
sql_index_exists('rights', 'modifiedby', 'ALTER TABLE `rights` DROP INDEX `modifiedby`');
sql_index_exists('roles' , 'modifiedby', 'ALTER TABLE `roles`  DROP INDEX `modifiedby`');

sql_column_exists('rights', 'modifiedon', 'ALTER TABLE `rights` DROP COLUMN `modifiedon`');
sql_column_exists('roles' , 'modifiedon', 'ALTER TABLE `roles`  DROP COLUMN `modifiedon`');
sql_column_exists('rights', 'modifiedby', 'ALTER TABLE `rights` DROP COLUMN `modifiedby`');
sql_column_exists('roles' , 'modifiedby', 'ALTER TABLE `roles`  DROP COLUMN `modifiedby`');

$servers = sql_query('SELECT `id` FROM `servers`');
log_console(tr('Assigning missing meta_id to all servers...'), 'cyan', false);

while ($servers_id = sql_fetch($servers, true)) {
    meta_link($servers_id, 'servers');
    cli_dot();
}

cli_dot(false);

/*
 * Fix foreign key names for forwardings table
 */
sql_foreignkey_exists('forwardings', 'fk_forwards_createdby' , 'ALTER TABLE `forwardings` DROP FOREIGN KEY `fk_forwards_createdby`');
sql_foreignkey_exists('forwardings', 'fk_forwards_source_id' , 'ALTER TABLE `forwardings` DROP FOREIGN KEY `fk_forwards_source_id`');
sql_foreignkey_exists('forwardings', 'fk_forwards_servers_id', 'ALTER TABLE `forwardings` DROP FOREIGN KEY `fk_forwards_servers_id`');

sql_foreignkey_exists('forwardings', 'fk_forwardings_createdby' , '!ALTER TABLE `forwardings` ADD CONSTRAINT `fk_forwardings_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`   (`id`) ON DELETE RESTRICT');
sql_foreignkey_exists('forwardings', 'fk_forwardings_source_id' , '!ALTER TABLE `forwardings` ADD CONSTRAINT `fk_forwardings_source_id`  FOREIGN KEY (`source_id`)  REFERENCES `servers` (`id`) ON DELETE RESTRICT');
sql_foreignkey_exists('forwardings', 'fk_forwardings_servers_id', '!ALTER TABLE `forwardings` ADD CONSTRAINT `fk_forwardings_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT');

?>