<?php

declare(strict_types=1);

namespace Phoundation\Processes;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\Select;


/**
 * Class Tasks
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
class Tasks extends DataList
{
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
            ->setSourceQuery('SELECT `id`, CONCAT(`command`, " [", `status`, "]") AS `command` FROM `' . $this->table . '` WHERE `status` IS NULL ORDER BY `created_on` ASC')
            ->setName('tasks_id')
            ->setNone(tr('Please select a task'))
            ->setEmpty(tr('No tasks available'));
    }
}