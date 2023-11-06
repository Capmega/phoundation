<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Core\Strings;
use Phoundation\Web\Html\Enums\InputType;


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
        return $this->attributes->get('minlength', false);
    }


    /**
     * Returns the minimum length this text input
     *
     * @param int|null $minlength
     * @return $this
     */
    public function setMinLength(?int $minlength): static
    {
        return $this->setAttribute($minlength, 'minlength');
    }


    /**
     * Returns the maximum length this text input
     *
     * @return int|null
     */
    public function getMaxLength(): ?int
    {
        return $this->attributes->get('maxlength', false);
    }


    /**
     * Returns the maximum length this text input
     *
     * @param int|null $maxlength
     * @return $this
     */
    public function setMaxLength(?int $maxlength): static
    {
        return $this->setAttribute($maxlength, 'maxlength');
    }


    /**
     * Returns the auto complete setting
     *
     * @return bool
     */
    public function getAutoComplete(): bool
    {
        return Strings::toBoolean($this->attributes->get('autocomplete', false));
    }


    /**
     * Sets the auto complete setting
     *
     * @param bool $auto_complete
     * @return $this
     */
    public function setAutoComplete(bool $auto_complete): static
    {
        return $this->setAttribute($auto_complete ? 'on' : 'off', 'autocomplete');
    }
}