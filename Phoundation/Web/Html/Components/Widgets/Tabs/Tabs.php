<?php

/**
 * Tabs class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Tabs;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\Traits\TraitDataOrientation;
use Phoundation\Enums\EnumOrientation;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Widgets\Tabs\Interfaces\TabInterface;
use Phoundation\Web\Html\Components\Widgets\Tabs\Interfaces\TabsInterface;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Traits\TraitButtons;
use Stringable;

class Tabs extends ElementsBlock implements TabsInterface
{
    use TraitButtons;
    use TraitDataOrientation {
        setOrientation as protected __setOrientation;
    }

    /**
     * The display size of the contents
     *
     * @var EnumDisplaySize
     */
    protected EnumDisplaySize $content_display_size = EnumDisplaySize::ten;


    /**
     * Tabs class constructor
     *
     * @param ArrayableInterface|array|null $source
     */
    public function __construct(ArrayableInterface|array|null $source = null)
    {
        parent::__construct($source);
        $this->orientation = EnumOrientation::top;
    }


    /**
     * Returns the display size for tab contents (left and right orientation only)
     *
     * @return EnumDisplaySize
     */
    public function getContentDisplaySize(): EnumDisplaySize
    {
        return $this->content_display_size;
    }


    /**
     * Sets the display size for tab contents (left and right orientation only)
     *
     * @param EnumDisplaySize $content_display_size
     *
     * @return static
     */
    public function setContentDisplaySize(EnumDisplaySize $content_display_size): static
    {
        $this->content_display_size = $content_display_size;

        return $this;
    }


    /**
     * Add tab to this Tabs object
     *
     * @param mixed                            $value
     * @param float|Stringable|int|string|null $key
     * @param bool                             $skip_null
     * @param bool                             $exception
     *
     * @return $this
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): static
    {
        if (!($value instanceof TabInterface)) {
            throw new OutOfBoundsException(tr('Specified tab ":value" should use a TabInterface', [
                ':value' => $value,
            ]));
        }

        return parent::add($value, $key, $skip_null, $exception);
    }
}
