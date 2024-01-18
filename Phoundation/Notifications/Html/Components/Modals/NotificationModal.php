<?php

declare(strict_types=1);

namespace Phoundation\Notifications\Html\Components\Modals;

use Phoundation\Web\Html\Components\Modals\LargeModal;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Enums\JavascriptWrappers;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * NotificationModal class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class NotificationModal extends LargeModal
{
    /**
     * Render the HTML for this sign-in modal
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // Set defaults
        $this->setId('notification-modal')
            ->setTitle(':title')
            ->setContent(':content');

        // Add the modal HTML to the page footer as it should be attached to the body tag directly
        Page::addToFooter('html', parent::render());

        // Render the sign in modal.
        return Script::new()
            ->setJavascriptWrapper(JavascriptWrappers::window)
            ->setContent('
            $("nav.main-header").on("click", ".notification.open-modal", function(e) {
                e.stopPropagation();

                $.get("' . UrlBuilder::getAjax('system/notifications/modal.json?id=') . '" + $(e.target).data("id"))
                    .done(function (data, textStatus, jqXHR) {
                        checkNotifications();
                        $("#notification-modal").find(".modal-title").text(data.title);
                        $("#notification-modal").find(".modal-body").html(data.body);
                        $("#notification-modal").find(".modal-footer").html(data.buttons);
                        $("#notification-modal").modal({show: true});
                    });

                return false;
            })')->render();
    }
}