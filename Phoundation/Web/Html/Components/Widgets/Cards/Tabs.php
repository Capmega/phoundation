<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Cards;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\DataOrientation;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Widgets\Cards\Interfaces\TabInterface;
use Phoundation\Web\Html\Components\Widgets\Cards\Interfaces\TabsInterface;
use Stringable;


/**
 * Tabs class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Tabs extends Iterator implements TabsInterface
{
    use DataOrientation;


    /**
     * Tabs class constructor
     *
     * @param ArrayableInterface|array|null $source
     */
    public function __construct(ArrayableInterface|array|null $source = null)
    {
        parent::__construct($source);
        $this->orientation = 'top';
    }


    /**
     * Add tab to this tabs object
     *
     * @param mixed $value
     * @param float|Stringable|int|string|null $key
     * @param bool $skip_null
     * @return $this
     */
    public function add(mixed $value, float|Stringable|int|string|null $key = null, bool $skip_null = true): static
    {
        if (!($value instanceof TabInterface)) {
            throw new OutOfBoundsException(tr('Specified tab ":value" should use a TabInterface', [
                ':value' => $value
            ]));
        }

        return parent::add($value, $key, $skip_null);
    }
}
