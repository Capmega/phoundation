<?php

/**
 * Class Breadcrumbs
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation/Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Breadcrumbs;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Widgets\Interfaces\BreadcrumbsInterface;


class Breadcrumbs extends ElementsBlock implements BreadcrumbsInterface
{
    /**
     * Breadcrumbs class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null) {
        parent::__construct($source);
        $this->setAcceptedDataTypes(Breadcrumb::class);
    }
}
