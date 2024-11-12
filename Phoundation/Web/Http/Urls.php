<?php

/**
 * Class Urls
 *
 * This class can manage a list of URL's
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Http;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Http\Interfaces\UrlsInterface;

class Urls extends Iterator implements UrlsInterface
{
    /**
     * Urls class constructor
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     */
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null) {
        $this->setAcceptedDataTypes(UrlInterface::class);
        parent::__construct($source);
    }
}
