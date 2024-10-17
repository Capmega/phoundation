<?php

/**
 * Class SetupPage
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Pages;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataEmail;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Html\Components\ElementsBlock;


class SetupPage extends ElementsBlock
{
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null) {
        throw new UnderConstructionException();

        parent::__construct($source);
    }
}
