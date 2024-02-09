<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Traits\DataStatus;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Notifications\Interfaces\NotificationsInterface;
use Phoundation\Notifications\Notifications;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Http\Interfaces\UrlBuilderInterface;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;


/**
 * NotificationsDropDown class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class NotificationsDropDown extends ElementsBlock
{
    use DataStatus {
        setStatus as setStatusTrait;
    }


    /**
     * The list of notifications
     *
     * @var Notifications|null $notifications
     */
    protected ?Notifications $notifications = null;

    /**
     * Contains the URL for the specific notifications
     *
     * @var UrlBuilderInterface|null $notifications_url
     */
    protected UrlBuilderInterface|null $notifications_url = null;

    /**
     * Contains the URL for the notifications page
     *
     * @var UrlBuilderInterface|null $notifications_all_url
     */
    protected UrlBuilderInterface|null $notifications_all_url = null;


    /**
     * Sets status and clears the notification cache
     *
     * @note: Overrides the trait setStatus()
     * @param string|null $status
     * @return $this
     */
    public function setStatus(?string $status): static
    {
        if ($this->status !== $status) {
            $this->notifications = null;
        }

        return $this->setStatusTrait($status);
    }


    /**
     * Returns the notifications object
     *
     * @return NotificationsInterface|null
     */
    public function getNotifications(): ?NotificationsInterface
    {
        if (!$this->notifications) {
            $this->notifications = new Notifications();
            $this->notifications->getQueryBuilder()->addSelect('`id` AS `_id`, `notifications`.*')->addOrderBy('`created_on` DESC');

            if ($this->status) {
                $this->notifications->getQueryBuilder()->addWhere('`users_id` = :users_id AND ' . Sql::is('`status`', $this->status, 'status'), [
                    ':users_id' => Session::getUser()->getId(),
                    ':status'   => $this->status
                ]);
            }

            $this->notifications->load();
        }

        return $this->notifications;
    }


    /**
     * Sets the notifications object
     *
     * @param Notifications|null $notifications
     * @return static
     */
    public function setNotifications(?Notifications $notifications): static
    {
        $this->notifications = $notifications;
        return $this;
    }


    /**
     * Returns the notifications page URL
     *
     * @return UrlBuilderInterface|null
     */
    public function getNotificationsUrl(): ?UrlBuilderInterface
    {
        return $this->notifications_url;
    }


    /**
     * Sets the notifications page URL
     *
     * @param Stringable|string|null $notifications_url
     * @return static
     */
    public function setNotificationsUrl(Stringable|string|null $notifications_url): static
    {
        $this->notifications_url = UrlBuilder::getWww($notifications_url);
        return $this;
    }


    /**
     * Returns the notifications page URL
     *
     * @return UrlBuilderInterface
     */
    public function getAllNotificationsUrl(): UrlBuilderInterface
    {
        return $this->notifications_all_url;
    }


    /**
     * Sets the notifications page URL
     *
     * @param Stringable|string|null $notifications_url
     * @return static
     */
    public function setAllNotificationsUrl(Stringable|string|null $notifications_url): static
    {
        $this->notifications_all_url = UrlBuilder::getWww($notifications_url);
        return $this;
    }


    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // Link the users notifications hash and see if we need to ping
        $ping = $this->getNotifications()->linkHash();

        if ($ping) {
            Script::new()
                ->setJavascriptWrapper(EnumJavascriptWrappers::window)
                ->setContent('console.log("Initial ping!"); $("audio.notification").trigger("play");')
                ->render();
        }

        return parent::render();
    }
}
