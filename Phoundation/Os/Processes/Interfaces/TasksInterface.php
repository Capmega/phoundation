<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;

interface TasksInterface extends DataIteratorInterface
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
    public function getHtmlSelect(string $value_column = '', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface;


    /**
     * Execute the tasks in this list
     *
     * @return static
     */
    public function execute(): static;
}
