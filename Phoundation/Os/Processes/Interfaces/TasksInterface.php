<?php

namespace Phoundation\Os\Processes\Interfaces;

use Phoundation\Os\Processes\Tasks;
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
interface TasksInterface
{
    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @param array|null $joins
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = '', string $key_column = 'id', ?string $order = null, ?array $joins = null): InputSelectInterface;

    /**
     * Execute the tasks in this list
     *
     * @return $this
     */
    public function execute(): static;
}
