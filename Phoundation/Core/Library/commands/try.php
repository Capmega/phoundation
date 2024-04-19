<?php

declare(strict_types=1);

/**
 * Script try
 *
 * General quick try and test script. Scribble any test code that you want to execute here and execute it with
 * ./pho test
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

use Phoundation\Utils\Arrays;
use Phoundation\Data\Iterator;
use Phoundation\Utils\Utils;

$a = [
    'sven' => tr('Sven'),
    'Svensen' => tr('Svensen'),
    'Svensven' => tr('Svensven'),
    'corey' => tr('Corey'),
    'Corey' => tr('Corey'),
    'Carey' => tr('Carey'),
    'doug' => tr('Doug'),
    'kate' => tr('Kate'),
    'kat' => tr('Kat'),
    'alice' => tr('Alice'),
    'bob' => tr('Bob'),
    'gerton' => tr('Gerton'),
    'Gerton' => tr('Gerton'),
    'Gertjan' => tr('Gertjan'),
];

$b = new Iterator($a);

show($a);
show(Arrays::removeMatchingKeys($a, 'e,a', Utils::MATCH_CONTAINS | Utils::MATCH_NOT | Utils::MATCH_ALL));
//show(Arrays::removeMatchingKeys($a, 'sven,corey,Kate,Quinn', Utils::MATCH_NOT));
//show($b->removeMatchingKeys('sven,corey,Kate')->getSource());