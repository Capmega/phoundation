<?php

/**
 * Class Severities
 *
 *
 *
 *
 * @see       DataIterator
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

namespace Phoundation\Security\Incidents;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;


class Severities extends Iterator
{
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null)
    {
        if (!$source) {
            $source = [
                'notice' => tr('Notice or higher'),
                'low'    => tr('Low or higher'),
                'medium' => tr('Medium or higher'),
                'high'   => tr('High or severe'),
                'severe' => tr('Severe only'),
            ];
        }

        parent::__construct($source);
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelect(): InputSelectInterface
    {
        return InputSelect::new()->setSource($this->source);
    }
}
