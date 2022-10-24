<?php
sql()->schema()->table('versions')
    ->setColumns('`id`        INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                  `createdon` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `class`     VARCHAR(64)   NOT NULL,
                  `version`   VARCHAR(64)   NOT NULL,
                  `comments`  VARCHAR(2048) NOT NULL')
    ->setIndices('INDEX (`createdon`),
                  INDEX (`class`),
                  INDEX (`version`),
                  INDEX (`class`, `version`)')
    ->create();
