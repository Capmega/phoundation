<?php

namespace Phoundation\Web\Http\Html\Components\Input;



/**
 * Class InputCheckbox
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputCheckbox extends Input
{
    /**
     * Sets if the checkbox is checked or not
     *
     * @var bool $checked
     */
    protected bool $checked = false;



    /**
     * InputCheckbox class constructor
     */
    public function __construct()
    {
        $this->type = 'checkbox';
        parent::__construct();
    }



    /**
     * Returns if the checkbox is checked or not
     *
     * @return bool
     */
    public function getChecked(): bool
    {
        return (bool) isset_get($this->attributes['checked']);
    }



    /**
     * Returns if the checkbox is checked or not
     *
     * @param bool $checked
     * @return static
     */
    public function setChecked(bool $checked): static
    {
        $this->attributes['checked'] = ($checked ? '' : null);
        return $this;
    }
}