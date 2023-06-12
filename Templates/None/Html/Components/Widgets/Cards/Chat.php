<?php

declare(strict_types=1);


namespace Templates\None\Html\Components\Widgets\Cards;

use Phoundation\Web\Http\Html\Renderer;


/**
 * None Plugin Chat class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
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