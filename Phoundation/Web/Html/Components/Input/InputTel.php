<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Html\Traits\TraitUsesAttributeMultiple;


/**
 * Class InputTel
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputTel extends InputText
{
    use TraitUsesAttributeMultiple;

    /**
     * InputTel class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->type = EnumInputType::tel;
        parent::__construct($content);
    }
}