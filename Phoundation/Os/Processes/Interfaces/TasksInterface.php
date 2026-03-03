<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
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
    public function getHtmlSelectOld(string $value_column = '', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface;


    /**
     * Execute the tasks in this list
     *
     * @return static
     */
    public function execute(): static;

    /**
     * Returns whether this class should continue execution when no tasks are available anymore for execution
     *
     * @return bool
     */
    public function getContinueAfterFinish(): bool;

    /**
     * Sets whether this class should continue execution when no tasks are available anymore for execution
     *
     * @param bool $continue_after_finish
     *
     * @return static
     */
    public function setContinueAfterFinish(bool $continue_after_finish): static;
}
