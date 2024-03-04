<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Pages;

use Phoundation\Data\Traits\DataEmail;
use Phoundation\Web\Html\Components\ElementsBlock;


/**
 * Class UpdateLostPasswordPage
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class UpdateLostPasswordPage extends ElementsBlock
{
    use DataEmail;


    /**
     * SignIn class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
    }
}