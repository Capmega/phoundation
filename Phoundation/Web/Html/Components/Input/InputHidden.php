<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Enums\EnumElementInputType;


/**
 * Class InputHidden
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputHidden extends Input
{
    /**
     * InputHidden class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->input_type = EnumElementInputType::hidden;
        parent::__construct($content);
    }


    /**
     * Sets the HTML class element attribute
     *
     * @param bool $auto_focus
     * @return static
     */
    public function setAutofocus(bool $auto_focus): static
    {
        if ($auto_focus) {
            throw new OutOfBoundsException(tr('The HTML hidden input element ":name" is not visible and thus cannot receive auto focus', [
                ':name' => $this->name
            ]));
        }

        return $this;
    }
}