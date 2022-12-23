<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Elements\ElementsBlock;



/**
 * Class DataEntryForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Web
 */
class DataEntryForm extends ElementsBlock
{
    /**
     * The data source for this form
     *
     * @var array $keys
     */
    protected array $keys;



    /**
     * Returns the data source for this DataEntryForm
     *
     * @return array
     */
    public function getKeys(): array
    {
        return $this->keys;
    }



    /**
     * Set the data source for this DataEntryForm
     *
     * @param array $keys
     * @return static
     */
    public function setKeys(array $keys): static
    {
        $this->keys = $keys;
        return $this;
    }



    /**
     * Standard DataEntryForm object does not render any HTML, this requires a Template class
     *
     * @return string
     */
    public function render(): string
    {
        if (!isset($this->source)) {
            throw new OutOfBoundsException(tr('Cannot render DataEntryForm, no data source specified'));
        }

        return '';
    }
}