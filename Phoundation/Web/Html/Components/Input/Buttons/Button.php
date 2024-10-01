<?php

/**
 * Button class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Buttons;

use Phoundation\Web\Html\Components\Icons\Icons;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Input;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Traits\TraitButtonProperties;
use Stringable;


class Button extends Input implements ButtonInterface
{
    use TraitButtonProperties;


    /**
     * Button class constructor
     *
     * @param string|null $content
     *
     * @todo Get rid of the web.defaults.elements.classes.button path as this was an idea before the templating system
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);

        $this->setName('submit')
             ->setValue($content)
             ->setClasses('btn')
             ->setElement('button')
             ->setButtonType(EnumButtonType::submit);
    }


    /**
     * Set the content for this button
     *
     * @param Stringable|string|float|int|null $value
     * @param bool                             $make_safe
     *
     * @return static
     * @todo add documentation for when button is floating as it is unclear what is happening there
     */
    public function setValue(Stringable|string|float|int|null $value, bool $make_safe = true): static
    {
        if ($this->floating) {
            // What does this do?????????????
            $this->addClasses('btn-floating');

            Icons::new()
                 ->setContent($this->content)
                 ->render();

            return $this;
        }

        parent::setValue($value, $make_safe);

        return parent::setContent($value, $make_safe);
    }


    /**
     * Set the content for this button
     *
     * @param Stringable|string|float|int|null $content
     * @param bool                             $make_safe
     *
     * @return static
     * @todo add documentation for when button is floating as it is unclear what is happening there
     */
    public function setContent(Stringable|string|float|int|null $content, bool $make_safe = false): static
    {
        if ($this->floating) {
            // What does this do?????????????
            $this->addClasses('btn-floating');

            Icons::new()
                 ->setContent($this->content, $make_safe)
                 ->render();

            return $this;
        }

        return parent::setContent($content, $make_safe);
    }
}
