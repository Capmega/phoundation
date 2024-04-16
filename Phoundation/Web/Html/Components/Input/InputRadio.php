<?php
/**
 * Class InputRadio
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Traits\TraitDataInline;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Html\Traits\TraitInputChecked;
use Phoundation\Web\Html\Traits\TraitInputCheckRadioRender;

class InputRadio extends Input
{
    use TraitDataInline;
    use TraitInputChecked;
    use TraitInputCheckRadioRender;

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


    /**
     *
     * Overrides ElementAttributes::setName()
     *
     * @param string|null $name
     * @param bool        $id_too
     *
     * @return $this
     */
    public function setName(?string $name, bool $id_too = true): static
    {
        static $names = [];
        if (!isset($names[$name])) {
            $names[$name] = 0;
        }
        if ($id_too) {
            $this->setId($name . '-' . $names[$name]++);
        }

        return parent::setName($name);
    }
}
