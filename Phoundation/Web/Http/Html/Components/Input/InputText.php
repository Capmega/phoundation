<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Core\Strings;
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
        $this->type = $this->type ?? InputType::text;
        parent::__construct();
    }


    /**
     * Returns the minimum length this text input
     *
     * @return int|null
     */
    public function getMinLength(): ?int
    {
        return isset_get($this->attributes['minlength']);
    }


    /**
     * Returns the minimum length this text input
     *
     * @param int|null $minlength
     * @return $this
     */
    public function setMinLength(?int $minlength): static
    {
        $this->attributes['minlength'] = $minlength;
        return $this;
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


    /**
     * Returns the auto complete setting
     *
     * @return bool
     */
    public function getAutoComplete(): bool
    {
        return Strings::toBoolean(isset_get($this->attributes['autocomplete']));
    }


    /**
     * Sets the auto complete setting
     *
     * @param bool $auto_complete
     * @return $this
     */
    public function setAutoComplete(bool $auto_complete): static
    {
        $this->attributes['autocomplete'] = ($auto_complete ? 'on' : 'off');
        return $this;
    }
}