<?php

declare(strict_types=1);

namespace Phoundation\Core\Locale\Language\Interfaces;


use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;

/**
 * Languages class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
interface LanguagesInterface
{
    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null $joins
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', ?string $key_column = 'id', ?string $order = null, ?array $joins = null): InputSelectInterface;

    /**
     * Load the id list from the database
     *
     * @param bool $clear
     * @return static
     */
    public function load(bool $clear = true, bool $only_if_empty = false): static;
}
