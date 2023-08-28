<?php

namespace Phoundation\Core\Locale\Language\Interfaces;


use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\InputSelectInterface;

/**
 * Languages class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
interface LanguagesInterface
{
    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id', ?string $order = null): InputSelectInterface;

    /**
     * @return $this
     * @throws \Throwable
     */
    public function load(): static;
}
