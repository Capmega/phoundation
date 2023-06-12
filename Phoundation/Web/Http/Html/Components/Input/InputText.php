<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Web\Http\Html\Enums\InputType;

/**
 * Class InputText
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputText extends Input
{
    /**
     * InputText class constructor
     */
    public function __construct()
    {
        $this->type = InputType::text;
        parent::__construct();
    }


    /**
     * Returns the maximum length this text input
     *
     * @return int|null
     */
    public function getMaxLength(): ?int
    {
        return isset_get($this->attributes['maxlength']);
    }


    /**
     * Returns the maximum length this text input
     *
     * @param int|null $maxlength
     * @return $this
     */
    public function setMaxLength(?int $maxlength): static
    {
        $this->attributes['maxlength'] = $maxlength;
        return $this;
    }
}