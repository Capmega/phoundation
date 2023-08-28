<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Web\Http\Html\Enums\InputElement;


/**
 * Class FilterForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class FilterForm extends DataEntryForm
{
    /**
     * All get filter data
     *
     * @var array $get
     */
    protected array $get;


    /**
     * Returns filter value for the specified key
     *
     * @param string $key
     * @return mixed
     */
    public function getFilterValue(string $key): mixed
    {
        return isset_get($this->get[$key]);
    }


    /**
     * Sets the filter value for the specified key
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setFilterValue(mixed $value, string $key): static
    {
        $this->get[$key] = $value;
        return $this;
    }


    /**
     * FilterForm class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('filters');
    }
}
