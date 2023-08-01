<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Components\Widgets\Cards;

use Phoundation\Web\Http\Html\Renderer;


/**
 * AdminLte Plugin Chat class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class Chat extends Renderer
{
    /**
     * Chat class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Widgets\Cards\Chat $element)
    {
        parent::__construct($element);
    }
}