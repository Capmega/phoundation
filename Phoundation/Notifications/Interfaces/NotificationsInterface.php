<?php

namespace Phoundation\Notifications\Interfaces;


use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;

/**
 * Notifications class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundations\Notifications
 */
interface NotificationsInterface
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
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id', ?string $order = null): InputSelectInterface;

    /**
     * Marks the severity column with a color class
     *
     * @return $this
     */
    public function markSeverityColumn(): static;

    /**
     * Have the client perform automated update checks for notifications
     *
     * @return $this
     */
    public function autoUpdate(): static;

    /**
     * Return a sha1 hash of all notification ID's available to this user
     *
     * @return ?string
     */
    public function getHash(): ?string;
}
