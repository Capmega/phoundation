<?php

declare(strict_types=1);

namespace Phoundation\Web\Requests\Routing\Interfaces;


/**
 * Interface MappingsInterface
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface MappingInterface
{
    public function apply($url): string;
}
