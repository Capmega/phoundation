<?php

namespace Phoundation\Os\Processes\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;


/**
 * Class Tasks
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
interface TasksInterface extends DataListInterface
{
    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null $joins
     * @param array|null $filters
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = '', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface;

    /**
     * Execute the tasks in this list
     *
     * @return $this
     */
    public function execute(): static;
}
