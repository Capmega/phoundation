<?php

declare(strict_types=1);

namespace Phoundation\Processes;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\InputSelect;


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
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @return SelectInterface
     */
    public function getHtmlSelect(string $value_column = '', string $key_column = 'id'): SelectInterface
    {
        if (!$value_column) {
            $value_column = 'CONCAT(`command`, " [", `status`, "]") AS command';
        }

        return InputSelect::new()
            ->setSourceQuery('SELECT   `' . $key_column . '`, ' . $value_column . ' 
                                         FROM     `' . $this->table . '` 
                                         WHERE    `status` IS NULL 
                                         ORDER BY `created_on` ASC')
            ->setName('tasks_id')
            ->setNone(tr('Select a task'))
            ->setEmpty(tr('No tasks available'));
    }
}