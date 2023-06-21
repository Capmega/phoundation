<?php

declare(strict_types=1);

namespace Phoundation\Messages;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;


/**
 * Messages class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundations\Messages
 */
class Messages extends DataList
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
        $this->table       = 'messages';

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
    protected function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // TODO: Implement loadDetails() method.
    }


    /**
     * @inheritDoc
     */
    public function save(): bool
    {
        // TODO: Implement save() method.
    }


    /**
     * Returns an HTML select component object containing the entries in this list
     *
     * @return SelectInterface
     */
    public function getHtmlSelect(): SelectInterface
    {
        // TODO: Implement getHtmlSelect() method.
    }
}