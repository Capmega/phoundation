<?php

/**
 * LargeModal class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Modals;

class LargeModal extends Modal
{
    /**
     * LargeModal class constructor
     *
     * @param string|null $source
     */
    public function __construct(?string $source = null)
    {
        parent::__construct($source);
        $this->setSize('lg');
    }
}
