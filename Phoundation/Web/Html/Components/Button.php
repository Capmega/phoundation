<?php

/**
 * Button class
 *
 * This class can render <button> elements
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Web\Html\Enums\EnumElementButtonType;

class Button extends Span
{
    /**
     * Form class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        $this->setElement('button')
             ->setType(EnumElementButtonType::button)
             ->addClass('btn');
    }

    /**
     * Sets the type for this element block
     *
     * @param EnumElementButtonType|null $type
     *
     * @return static
     */
    public function setType(?EnumElementButtonType $type): static
    {
        $this->attributes->set($type->value, 'type');
        return $this;
    }

    /**
     * Returns the type for this element block
     *
     * @return EnumElementButtonType|null
     */
    public function getType(): ?EnumElementButtonType
    {
        $type = $this->attributes->get('type');

        if ($type) {
            return EnumElementButtonType::from($type);
        }

        return null;
    }
}
