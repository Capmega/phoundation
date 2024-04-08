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
use Phoundation\Web\Html\Enums\EnumElementInputType;
use Phoundation\Web\Html\Traits\TraitInputChecked;
use Phoundation\Web\Html\Traits\TraitInputCheckRadioRender;
use Phoundation\Web\Html\Traits\TraitInputElement;

class InputCheckbox extends Input
{
    use TraitInputElement;
    use TraitDataInline;
    use TraitInputChecked;
    use TraitInputCheckRadioRender;

    /**
     * CheckBox class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->setElement('input')
             ->setInputType(EnumElementInputType::checkbox)
             ->setValue(1);
        parent::__construct($content);
    }
}
