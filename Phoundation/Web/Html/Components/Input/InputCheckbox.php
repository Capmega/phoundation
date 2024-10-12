<?php

/**
 * Class InputCheckbox
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation/Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Traits\TraitDataInline;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Html\Traits\TraitInputChecked;
use Phoundation\Web\Html\Traits\TraitInputCheckboxRadioRender;


class InputCheckbox extends Input
{
    use TraitDataInline;
    use TraitInputChecked;
    use TraitInputCheckboxRadioRender;

    /**
     * CheckBox class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->setElement('input')
             ->setInputType(EnumInputType::checkbox)
             ->setValue(1);
        parent::__construct($content);
    }


    /**
     * Sets the HTML readonly AND disabled element attribute
     *
     * @param bool $readonly
     *
     * @return static
     */
    public function setReadonly(bool $readonly): static
    {
               parent::setReadonly($readonly);
        return parent::setDisabled($readonly);
    }


    /**
     * Sets the HTML readonly AND disabled element attribute
     *
     * @param bool $disabled
     *
     * @return static
     */
    public function setDisabled(bool $disabled): static
    {
               parent::setDisabled($disabled);
        return parent::setReadonly($disabled);
    }
}
