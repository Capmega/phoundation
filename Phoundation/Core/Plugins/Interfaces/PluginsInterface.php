<?php

declare(strict_types=1);

namespace Phoundation\Core\Plugins\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;

/**
 * Interface PluginsInterface
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */
interface PluginsInterface extends DataListInterface
{
    /**
     * Purges all plugins from the DIRECTORY_ROOT/Plugins path
     *
     * @return static
     */
    public function purge(): static;


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string      $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null  $joins
     * @param array|null  $filters
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface;
}
