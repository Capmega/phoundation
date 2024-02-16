<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies\Branches\Interfaces;

use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;


/**
 * Class Branches
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
interface BranchesInterface
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
    public function getHtmlSelect(string $value_column = 'name', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface;
}
