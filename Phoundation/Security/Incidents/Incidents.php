<?php

declare(strict_types=1);

namespace Phoundation\Security\Incidents;

use PDOStatement;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;


/**
 * Class Incidents
 *
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 */
class Incidents extends DataList
{
    /**
     * Incidents class constructor
     */
    public function __construct()
    {
        $this->entry_class = Incident::class;
        $this->table       = 'security_incidents';

        $this->setQuery('SELECT   `id`, `type`, `severity`, `title` 
                                   FROM     `security_incidents` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `created_on` DESC');
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
        return parent::getHtmlSelect()
            ->setName('incidents_id')
            ->setNone(tr('Please select an incident'))
            ->setEmpty(tr('No incidents available'));
    }
}