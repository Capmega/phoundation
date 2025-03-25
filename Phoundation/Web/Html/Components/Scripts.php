<?php

/**
 * Class Scripts
 *
 * This class manages collections of JavaScript Script classes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitMethodHasRendered;
use Phoundation\Web\Html\Components\Interfaces\ScriptInterface;
use Phoundation\Web\Html\Components\Interfaces\ScriptsInterface;


class Scripts extends Iterator implements ScriptsInterface
{
    use TraitMethodHasRendered;


    /**
     * Scripts class constructor
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     */
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null)
    {
        parent::__construct($source);
        $this->setAcceptedDataTypes(ScriptInterface::class);
    }


    /**
     * Renders and returns all Script classes in this Iterator
     *
     * @note Since Script class renders may be attached to page headers or footers (in which case that Script class
     *       would return NULL for rendering) this method may return NULL even if it rendered multiple Script classes.
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $return = null;

        if ($this->count()) {
            foreach ($this as $o_script) {
                $return .= $o_script->render();
            }

            return $return;
        }

        return null;
    }
}