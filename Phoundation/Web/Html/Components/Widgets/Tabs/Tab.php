<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Tabs;

use Phoundation\Data\Traits\TraitDataLabel;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Widgets\Tabs\Interfaces\TabInterface;


/**
 * Tab class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Tab extends ElementsBlock implements TabInterface
{
    use TraitDataLabel;


    /**
     * @return string|null
     */
    public function render(): ?string
    {
        if (empty($this->label)) {
            throw new OutOfBoundsException(tr('Cannot render tab, no label specified'));
        }

        return parent::render();
    }


    /**
     * @inheritDoc
     */
    public function getId(): ?string
    {
        if (empty($this->id)) {
            $this->id = Strings::random(16, characters: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
        }

        return $this->id;
    }
}