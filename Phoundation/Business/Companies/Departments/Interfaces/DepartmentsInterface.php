<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies\Departments\Interfaces;

use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;

interface DepartmentsInterface
{
    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string      $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null  $joins
     * @param array|null  $filters
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface;
}
