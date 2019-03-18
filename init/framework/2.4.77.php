<?php
/*
 * Fix translations table unique index
 */
sql_index_exists('dictionary', 'string'     , '!ALTER TABLE `dictionary` ADD KEY `string`      (`string` (100))');
sql_index_exists('dictionary', 'sources_id' , '!ALTER TABLE `dictionary` ADD KEY `sources_id`  (`sources_id`)');
sql_index_exists('dictionary', 'projects_id', '!ALTER TABLE `dictionary` ADD KEY `projects_id` (`projects_id`)');

sql_foreignkey_exists('dictionary', 'fk_dictionary_projects_id' , '!ALTER TABLE `dictionary` ADD CONSTRAINT `fk_dictionary_projects_id` FOREIGN KEY (`projects_id`) REFERENCES `projects` (`id`) ON DELETE RESTRICT;');

$delete     = sql_prepare('DELETE FROM `dictionary` WHERE `id` = :id');
$duplicates = sql_query('SELECT   `dictionary_duplicates`.`projects_id`,
                                  `dictionary_duplicates`.`file`,
                                  `dictionary_duplicates`.`string`

                         FROM     `dictionary`

                         JOIN     `dictionary` AS `dictionary_duplicates`
                         ON       `dictionary`.`projects_id`  = `dictionary_duplicates`.`projects_id`
                         AND      `dictionary`.`id`          != `dictionary_duplicates`.`id`
                         AND      `dictionary`.`file`         = `dictionary_duplicates`.`file`
                         AND      `dictionary`.`string`       = `dictionary_duplicates`.`string`

                         GROUP BY `dictionary_duplicates`.`file`, `dictionary_duplicates`.`string`');

log_console(tr('Filtering double `dictionary` entries'), null, false);

while($duplicate = sql_fetch($duplicates)){
    $count = 2;

    while($count > 1){
        $id = sql_get('SELECT `id`

                       FROM   `dictionary`

                       WHERE  `projects_id` = :projects_id
                       AND    `file`        = :file
                       AND    `string`      = :string

                       ORDER BY `id` DESC

                       LIMIT 1',

                       true, array(':projects_id' => $duplicate['projects_id'],
                                   ':file'        => $duplicate['file'],
                                   ':string'      => $duplicate['string']));

        if(!$id){
            break;
        }

        $delete->execute(array(':id' => $id));

        $count = sql_get('SELECT COUNT(`id`) AS `count`

                          FROM   `dictionary`

                          WHERE  `projects_id` = :projects_id
                          AND    `file`        = :file
                          OR     `string`      = :string',

                          true, array(':projects_id' => $duplicate['projects_id'],
                                      ':file'        => $duplicate['file'],
                                      ':string'      => $duplicate['string']));

        log_console(tr('Filtering double `dictionary` entry ":id"', array(':id' => $id)), 'DOT');
    }
}

cli_dot(false);
?>
