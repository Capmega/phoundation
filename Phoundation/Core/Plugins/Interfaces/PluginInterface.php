<?php

declare(strict_types=1);

namespace Phoundation\Core\Plugins\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;


interface PluginInterface
{
    /**
     * Returns the plugin description
     *
     * @return string|null
     */
    public function getDescription(): ?string;


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
     *
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
     * @param int|bool|null     $disabled
     * @param bool|null         $set_readonly (ignored)
     * @param string|false|null $title        (ignored)
     *
     * @return static
     */
    public function setDisabled(int|bool|null $disabled, ?bool $set_readonly = null, string|false|null $title = null): static;


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
     *
     * @return static
     */
    public function setClass(?string $class): static;


    /**
     * Sets the priority for this plugin
     *
     * @param int|null $priority
     *
     * @return static
     */
    public function setPriority(?int $priority): static;


    /**
     * Returns the plugin path for this plugin
     *
     * @return PhoDirectoryInterface
     */
    public function getDirectoryObject(): PhoDirectoryInterface;


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
     *
     * @return void
     */
    public function unregister(?string $comments = null): void;


    /**
     * Enable this plugin
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function enable(?string $comments = null): static;


    /**
     * Disable this plugin
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function disable(?string $comments = null): static;
}
