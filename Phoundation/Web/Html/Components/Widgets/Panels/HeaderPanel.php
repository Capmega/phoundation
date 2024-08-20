<?php

/**
 * HeaderPanel class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Panels;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Utils\Config;


class HeaderPanel extends Panel
{
    /**
     * @var bool $mini
     */
    protected bool $mini;


    /**
     * HeaderPanel class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null)
    {
        parent::__construct($source);
        $this->mini = Config::getBoolean('web.panels.header.mini', false);
    }


    /**
     * Returns true if a mini header panel will be rendered
     *
     * @return bool
     */
    public function getMini(): bool
    {
        return $this->mini;
    }


    /**
     * Sets if a mini header panel will be rendered
     *
     * @param bool $mini
     *
     * @return HeaderPanel
     */
    public function setMini(bool $mini): static
    {
        $this->mini = $mini;

        return $this;
    }
}
