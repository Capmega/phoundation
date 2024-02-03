<?php

declare(strict_types=1);

namespace Phoundation\Core\Plugins\Interfaces;


/**
 * Class Plugin
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
interface PluginInterface
{
    /**
     * Returns if this plugin is enabled or not
     *
     * @return bool
     */
    public function getEnabled(): bool;

    /**
     * Sets if this plugin is enabled or not
     *
     * @param int|bool|null $enabled
     * @return static
     */
    public function setEnabled(int|bool|null $enabled): static;

    /**
     * Returns if this plugin is disabled or not
     *
     * @return bool
     */
    public function getDisabled(): bool;

    /**
     * Sets if this plugin is disabled or not
     *
     * @param int|bool|null $disabled
     * @return static
     */
    public function setDisabled(int|bool|null $disabled): static;

    /**
     * Returns the plugin path for this plugin
     *
     * @return string|null
     */
    public function getClass(): ?string;

    /**
     * Sets the main class for this plugin
     *
     * @param string|null $class
     * @return static
     */
    public function setClass(?string $class): static;

    /**
     * Sets the priority for this plugin
     *
     * @param int|null $priority
     * @return static
     */
    public function setPriority(?int $priority): static;

    /**
     * Returns the plugin path for this plugin
     *
     * @return string|null
     */
    public function getPath(): ?string;

    /**
     * Returns the plugin name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Uninstalls this plugin
     *
     * @return void
     */
    public function uninstall(): void;

    /**
     * Delete the plugin from the plugin registry
     *
     * @param string|null $comments
     * @return void
     */
    public function unregister(?string $comments = null): void;

    /**
     * Enable this plugin
     *
     * @param string|null $comments
     * @return void
     */
    public function enable(?string $comments = null): void;

    /**
     * Disable this plugin
     *
     * @param string|null $comments
     * @return void
     */
    public function disable(?string $comments = null): void;
}
