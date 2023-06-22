<?php

declare(strict_types=1);

namespace Phoundation\Messages;

use PDOStatement;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\Select;


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
     */
    public function __construct()
    {
        $this->entry_class = Message::class;
        $this->table       = 'messages';

        $this->setQuery('SELECT   `id`, `title`, `status`, `created_on` 
                                   FROM     `messages` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `title`');
        parent::__construct();
    }


    /**
     * @inheritDoc
     */
    public function load(?string $id_column = null): static
    {
        // TODO: Implement load() method.
    }


    /**
     * @inheritDoc
     */
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
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


    /**
     * Returns an HTML select component object containing the entries in this list
     *
     * @return SelectInterface
     */
    public function getHtmlSelect(): SelectInterface
    {
        return Select::new()
            ->setSourceQuery('SELECT `id`, `title` FROM `' . $this->table . '` WHERE `status` IS NULL ORDER BY `title` ASC')
            ->setName('messages_id')
            ->setNone(tr('Please select a message'))
            ->setEmpty(tr('No messages available'));
    }
}