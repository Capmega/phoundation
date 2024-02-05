<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Cards;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\DataOrientation;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Widgets\Cards\Interfaces\TabInterface;
use Phoundation\Web\Html\Components\Widgets\Cards\Interfaces\TabsInterface;
use Phoundation\Web\Html\Enums\DisplaySize;
use Phoundation\Web\Html\Enums\Interfaces\DisplaySizeInterface;
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
class Tabs extends ElementsBlock implements TabsInterface
{
    use DataOrientation {
        setOrientation AS protected __setOrientation;
    }


    /**
     * The display size of the contents
     *
     * @var DisplaySizeInterface|DisplaySize
     */
    protected DisplaySizeInterface $content_display_size = DisplaySize::ten;


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
     * Returns the display size for tab contents (left and right orientation only)
     *
     * @return DisplaySizeInterface
     */
    public function getContentDisplaySize(): DisplaySizeInterface
    {
        return $this->content_display_size;
    }


    /**
     * Sets the display size for tab contents (left and right orientation only)
     *
     * @param DisplaySizeInterface $content_display_size
     * @return static
     */
    public function setContentDisplaySize(DisplaySizeInterface $content_display_size): static
    {
        $this->content_display_size = $content_display_size;
        return $this;
    }


    /**
     * Add tab to this Tabs object
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
