<?php

/**
 * Class ForcePasswordUpdatePage
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Pages;


class ForcePasswordUpdatePage extends Page
{
    /**
     * ForcePasswordUpdatePage class constructor
     *
     * @param string|null $name
     */
    public function __construct(?string $name) {
        parent::__construct($name);

        $this->addTexts([
            tr('Please update your account to have a new and secure password password before continuing...'),
        ]);
    }
}
