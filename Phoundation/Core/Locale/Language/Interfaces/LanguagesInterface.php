<?php

declare(strict_types=1);

namespace Phoundation\Core\Locale\Language\Interfaces;

use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;

interface LanguagesInterface
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


    /**
     * Load the id list from the database
     *
     * @param array|null $identifiers
     * @param bool       $clear
     * @param bool       $only_if_empty
     *
     * @return static
     */
    public function load(?array $identifiers = null, bool $clear = true, bool $only_if_empty = false): static;
}
