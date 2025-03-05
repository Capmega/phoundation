<?php

/**
 * Class InputRadio
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Traits\TraitDataInline;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Html\Traits\TraitInputChecked;
use Phoundation\Web\Html\Traits\TraitInputCheckboxRadioRender;


class InputRadio extends Input
{
    use TraitDataInline;
    use TraitInputChecked;
    use TraitInputCheckboxRadioRender;

    /**
     * InputRadio class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->setElement('input')
             ->setInputType(EnumInputType::radio)
             ->setValue(1);

        parent::__construct($content);
    }
}
