<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Core\Session;
use Phoundation\Data\Traits\DataStatus;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Notifications\Notifications;
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
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @return Notifications|null
     */
    public function getNotifications(): ?Notifications
    {
        if (!$this->notifications) {
            $this->notifications = new Notifications();
            $this->notifications->getQueryBuilder()->addSelect('`id` AS `_id`, `notifications`.*')->addOrderBy('`created_on` DESC');

            if ($this->status) {
                $this->notifications->getQueryBuilder()->addWhere('`users_id` = :users_id AND `status` ' . Sql::is($this->status, 'status'), [
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
        return parent::render();
    }
}