<?php

/**
 * Trait TraitDataFilterForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Components\Forms\Interfaces\FilterFormInterface;
use ReturnTypeWillChange;


trait TraitDataFilterForm
{
    /**
     * @var FilterFormInterface|null $filter_form
     */
    protected ?FilterFormInterface $filter_form = null;


    /**
     * Returns the filter_form
     *
     * @return FilterFormInterface
     */
    #[ReturnTypeWillChange] public function getFilterFormObject(): FilterFormInterface
    {
        return $this->filter_form;
    }


    /**
     * Sets the filter_form
     *
     * @param FilterFormInterface $filter_form
     *
     * @return static
     */
    public function setFilterFormObject(FilterFormInterface $filter_form): static
    {
        $this->filter_form = $filter_form;

        return $this;
    }
}
