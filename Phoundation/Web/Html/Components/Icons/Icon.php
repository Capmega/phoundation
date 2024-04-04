<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Icons;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Element;
use Phoundation\Web\Html\Components\Icons\Interfaces\IconInterface;
use Phoundation\Web\Html\Traits\TraitMode;


/**
 * Icon class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class Icon extends Element implements IconInterface
{
    use TraitMode;


    /**
     * Icon class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        if ($content) {
            $content = trim($content);

            if (!preg_match('/[a-z0-9-_ ]+/i', $content)) {
                // Icon names should only have letters, numbers and dashes and underscores. Multiple names may be
                // needed, so also allow spaces
                throw new OutOfBoundsException(tr('Invalid icon name ":icon" specified', [
                    ':icon' => $content,
                ]));
            }
        }

        parent::__construct(get_null($content));
        $this->setElement('i');
    }


    /**
     * Returns the icon for this object
     *
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->content;
    }


    /**
     * Sets the icon for this object
     *
     * @param string|null $icon
     * @param string      $subclass
     *
     * @return static
     */
    public function setIcon(?string $icon, string $subclass = ''): static
    {
        $this->content = $icon;
        return $this;
    }


    /**
     * @return string|null
     */
    public function render(): ?string
    {
        $this->addClass($this->getContent());
        $this->setContent(null);
        return parent::render();
    }
}