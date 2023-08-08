<?php

namespace Phoundation\Core\Plugins\Interfaces;


use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;

/**
 * Class Plugin
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
interface PluginsInterface
{
    /**
     * Truncates all plugins from the database table
     *
     * @return static
     */
    public function truncate(): static;

    /**
     * Purges all plugins from the PATH_ROOT/Plugins path
     *
     * @return static
     */
    public function purge(): static;

    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @return SelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id', ?string $order = null): SelectInterface;
}