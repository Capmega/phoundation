<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Icons;

use Phoundation\Core\Strings;
use Phoundation\Web\Html\Components\Icon;


/**
 * FontAwesome class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class FontAwesome extends Icon
{
    /**
     * Form class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addClasses(['far']);
    }


    /**
     * Returns the icon for this object
     *
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return Strings::startsNotWith($this->icon, 'fa-');
    }


    /**
     * Sets the icon for this object
     *
     * @param string|null $icon
     * @param string $subclass
     * @return static
     */
    public function setIcon(?string $icon, string $subclass = 'far fas fab'): static
    {
        $icon = strtolower(trim($icon));

        return parent::setIcon('fa-' . $icon, $subclass);
    }
}