<?php

namespace Phoundation\Notifications;

use Phoundation\Data\DataEntry\DataList;


/**
 * Notifications class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundations\Notifications
 */
class Notifications extends DataList
{
    /**
     * Notifications class constructor
     *
     * @param Notification|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Notification $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Notification::class;
        $this->table_name  = 'notifications';

        $this->setHtmlQuery('SELECT   `id`, `title`, `status`, `created_on` 
                                   FROM     `notifications` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `title`');
        parent::__construct($parent, $id_column);
    }



    /**
     * @inheritDoc
     */
    protected function load(?string $id_column = null): static
    {
        // TODO: Implement load() method.
    }

    /**
     * @inheritDoc
     */
    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }

    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }
}