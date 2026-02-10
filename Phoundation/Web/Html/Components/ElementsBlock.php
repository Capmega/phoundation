<?php

/**
 * Class ElementsBlock
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use PDOStatement;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Enums\EnumPoadTypes;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Poad\Poad;
use Phoundation\Web\Html\Components\Forms\Form;
use Phoundation\Web\Html\Components\Forms\Interfaces\FormInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Template\TemplateRenderer;
use Phoundation\Web\Requests\Request;
use Stringable;


abstract class ElementsBlock extends ElementsBlockCore
{
    /**
     * ElementsBlock class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null)
    {
        parent::__construct($source);
        $this->___construct();
    }


    /**
     * Returns a new static object
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     *
     * @return static
     */
    public static function new(IteratorInterface|PDOStatement|array|string|null $source = null): static
    {
        return new static($source);
    }
}
