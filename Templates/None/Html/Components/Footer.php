<?php

declare(strict_types=1);


namespace Templates\None\Html\Components;

use Phoundation\Core\Core;
use Phoundation\Web\Html\Renderer;


/**
 * None Plugin Footer class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class Footer extends Renderer
{
    /**
     * Footer class constructor
     */
    public function __construct(\Phoundation\Web\Html\Components\Footer $element)
    {
        parent::__construct($element);
    }


    /**
     * Renders and returns the HTML for the footer
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return '  <footer class="main-footer">
                    <div class="float-right d-none d-sm-block">
                      <b>' . tr('Phoundation (None template) Version') . '</b> ' . Core::FRAMEWORKCODEVERSION . '
                    </div>
                    <strong>Copyright © 2017-2024 <a href="https://phoundation.org">phoundation.org</a>.</strong> All rights reserved. <br>
                    <strong>Copyright © 2014-2021 <a href="https://adminlte.io">AdminLTE.io</a>.</strong> All rights reserved.
                  </footer>';
    }
}