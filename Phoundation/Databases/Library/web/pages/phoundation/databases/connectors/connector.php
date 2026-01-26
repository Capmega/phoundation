<?php

/**
 * Page databases/connectors/connector.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\AuditButton;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Input\Buttons\DeleteButton;
use Phoundation\Web\Html\Components\Input\Buttons\SaveButton;
use Phoundation\Web\Html\Components\Input\Buttons\UndeleteButton;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Validate GET and get the requested connector
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId(false, true)
                   ->validate();

$o_connector = Connector::new()->loadThis($get['id']);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Test'):
                // Test the connector
                try {
                    $o_connector->test();

                    Response::getFlashMessagesObject()->addSuccess(tr('The connector ":connector" has been tested successfully', [
                        ':connector' => $o_connector->getDisplayName(),
                    ]));

                } catch (\Phoundation\Exception\PhoException $e) {
                    Log::error($e);

                    Response::getFlashMessagesObject()->addWarning(tr('The connector ":connector" test failed, please check the logs', [
                        ':connector' => $o_connector->getDisplayName(),
                    ]));
                }

                // Redirect away from POST
                Response::redirect();

            case tr('Save'):
                // Update connector, roles, emails, and phones
                $o_connector->apply(false)->save();

                Response::getFlashMessagesObject()->addSuccess(tr('The connector ":connector" has been saved', [
                    ':connector' => $o_connector->getDisplayName(),
                ]));

                // Redirect away from POST
                Response::redirect(Url::new('/phoundation/databases/connectors/connector+' . $o_connector->getId() . '.html')->makeWww());

            case tr('Delete'):
                $o_connector->delete();
                Response::getFlashMessagesObject()->addSuccess(tr('The connector ":connector" has been deleted', [
                    ':connector' => $o_connector->getDisplayName(),
                ]));

                Response::redirect();

            case tr('Undelete'):
                $o_connector->undelete();
                Response::getFlashMessagesObject()->addSuccess(tr('The connector ":connector" has been undeleted', [
                    ':connector' => $o_connector->getDisplayName(),
                ]));

                Response::redirect();
        }

    } catch (IncidentsException | ValidationFailedException | AccessDeniedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessagesObject()->addMessage($e);
        $o_connector->forceApply();
    }
}


// Save button
if (!$o_connector->getReadonly()) {
    $o_save = SaveButton::new();
}


// Buttons.
if (!$o_connector->isNew()) {
    if (!$o_connector->isReadonly()) {
        if ($o_connector->isDeleted()) {
            $o_delete = UndeleteButton::new()
                                      ->setFloatRight(true);

        } else {
            $o_delete = DeleteButton::new()
                                    ->setFloatRight(true);

            // Audit button.
            $o_audit = AuditButton::new()
                                  ->setFloatRight(true)
                                  ->setUrlObject('/audit/meta+' . $o_connector->getMetaId() . '.html');
        }
    }

    // Test button.
    $o_test = Button::new()
                    ->setFloatRight(true)
                    ->setMode(EnumDisplayMode::information)
                    ->setContent(tr('Test'))
                    ->setContent(tr('Test'));
}


// Build the "connector" form
$o_connector_card = Card::new()
                        ->setCollapseSwitch(true)
                        ->setMaximizeSwitch(true)
                        ->setTitle(tr('Edit connector :name', [':name' => $o_connector->getDisplayName()]))
                        ->setContent($o_connector->getHtmlDataEntryFormObject())
                        ->setButtonsObject(Buttons::new()
                                                  ->addButton(isset_get($o_save))
                                                  ->addBackButton(Url::newPrevious('/phoundation/databases/connectors/connectors.html'), true)
                                                  ->addButton(isset_get($o_test))
                                                  ->addButton(isset_get($o_audit))
                                                  ->addButton(isset_get($o_delete))
                                                  ->addButton(isset_get($impersonate)));


// Build relevant links
$o_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new(Url::new('/phoundation/databases/databases.html')->makeWww(), tr('Manage databases')));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                                          <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                                          <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Set page meta-data
Response::setPageTitle(tr('Connector :connector', [':connector' => $o_connector->getDisplayName()]));
Response::setHeaderTitle(tr('Connector'));
Response::setHeaderSubTitle($o_connector->getDisplayName() . ($o_connector->sourceLoadedFromConfiguration() ? ' [' . tr('Configured') . ']' : ''));
Response::setBreadcrumbs([
    Breadcrumb::new('/'                                                , tr('Home')),
    Breadcrumb::new('/system-administration.html'                      , tr('System administration')),
    Breadcrumb::new('/phoundation/databases.html'                      , tr('Databases')),
    Breadcrumb::new('/phoundation/databases/connectors/connectors.html', tr('Connectors')),
    Breadcrumb::new(''                                                 , $o_connector->getDisplayName()),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_connector_card                       , EnumDisplaySize::nine, true)
           ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
