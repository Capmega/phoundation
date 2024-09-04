<?php

/**
 * Class Entry
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\Interfaces\EntryInterface;


class Entry extends EntryCore
{
    /**
     * Entry class constructor
     *
     * @param ArrayableInterface|array|null $source
     */
    public function __construct(ArrayableInterface|array|null $source = null) {
        $this->setSource($source);
    }


    /**
     * Returns a new EntryInterface object
     *
     * @param ArrayableInterface|array|null $source
     *
     * @return EntryInterface
     */
    public static function new(ArrayableInterface|array|null $source = null): EntryInterface
    {
        return new static($source);
    }
}
