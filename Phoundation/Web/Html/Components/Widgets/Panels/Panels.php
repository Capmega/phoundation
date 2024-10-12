<?php

/**
 * Panels class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation/Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Panels;

use Phoundation\Data\Iterator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Widgets\Panels\Interfaces\PanelInterface;
use Phoundation\Web\Html\Components\Widgets\Panels\Interfaces\PanelsInterface;
use Stringable;


class Panels extends Iterator implements PanelsInterface
{
    /**
     * @inheritDoc
     */
    public function add(mixed $value, float|Stringable|int|string|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        if (($value === null) or ($value instanceof PanelInterface)) {
            return parent::add($value, $key, $skip_null_values, $exception);
        }
        throw OutOfBoundsException::new(tr('Cannot add specified value type ":value" with key ":key", the value must be a PanelInterface type object', [
            ':key'   => $key,
            ':value' => get_class_or_data_type($value),
        ]))
                                  ->setData([
                                      'value' => $value,
                                  ]);
    }


    /**
     * @inheritDoc
     */
    public function get(float|Stringable|int|string $key, bool $exception = true): ?PanelInterface
    {
        return parent::get($key, $exception);
    }
}
