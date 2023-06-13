<?php

declare(strict_types=1);

namespace Phoundation\Messages;

use Phoundation\Data\DataEntry\DataListInterface;

/**
 * Messages class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataListInterface
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundations\Messages
 */
class Messages extends DataListInterface
{
    /**
     * Messages class constructor
     *
     * @param Message|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Message $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Message::class;
        self::$table       = Message::getTable();

        $this->setHtmlQuery('SELECT   `id`, `title`, `status`, `created_on` 
                                   FROM     `messages` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `title`');
        parent::__construct($parent, $id_column);
    }


    /**
     * @inheritDoc
     */
    protected function load(string|int|null $id_column = null): static
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