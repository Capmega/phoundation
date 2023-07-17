<?php

declare(strict_types=1);

namespace Phoundation\Processes;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Security\Incidents\Incident;
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
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'process_tasks';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return Task::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'code';
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
                                         FROM     `' . static::getTable() . '` 
                                         WHERE    `status` IS NULL 
                                         ORDER BY `created_on` ASC')
            ->setName('tasks_id')
            ->setNone(tr('Select a task'))
            ->setEmpty(tr('No tasks available'));
    }
}