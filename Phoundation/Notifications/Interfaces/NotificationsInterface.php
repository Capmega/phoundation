<?php

declare(strict_types=1);

namespace Phoundation\Notifications\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;

interface NotificationsInterface extends DataIteratorInterface
{
    /**
     * Returns the most important notification mode
     *
     * @return string
     */
    public function getMostImportantMode(): string;


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
     * Marks the severity column with a color class
     *
     * @return static
     */
    public function markSeverityColumn(): static;


    /**
     * Have the client perform automated update checks for notifications
     *
     * @return static
     */
    public function autoUpdate(): static;


    /**
     * Return a sha1 hash of all notification ID's available to this user
     *
     * @return ?string
     */
    public function getHash(): ?string;
}
