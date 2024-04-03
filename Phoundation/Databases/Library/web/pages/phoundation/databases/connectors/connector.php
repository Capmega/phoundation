<?php

/**
 * Page databases/connectors/connector.php
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */

declare(strict_types=1);

use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;

// Validate GET and get the requested connector
$get = GetValidator::new()
    ->select('id')->isOptional()->isDbId(false, true)
    ->validate();

$connector = Connector::new($get['id']);


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::getSubmitButton()) {
            case tr('Test'):
                // Test the connector
                try {
                    $connector->test();

                    Request::getFlashMessages()->addSuccessMessage(tr('The connector ":connector" has been tested successfully', [
                        ':connector' => $connector->getDisplayName()
                    ]));

                } catch (\Phoundation\Exception\Exception $e) {
                    Log::error($e);

                    Request::getFlashMessages()->addWarningMessage(tr('The connector ":connector" test failed, please check the logs', [
                        ':connector' => $connector->getDisplayName()
                    ]));
                }

                // Redirect away from POST
                Response::redirect();

            case tr('Save'):
                // Update connector, roles, emails, and phones
                $connector->setDebug(true)->apply(false)->save();

                Request::getFlashMessages()->addSuccessMessage(tr('The connector ":connector" has been saved', [
                    ':connector' => $connector->getDisplayName()
                ]));

                // Redirect away from POST
                Response::redirect(UrlBuilder::getWww('/phoundation/databases/connectors/connector+' . $connector->getId() . '.html'));

            case tr('Delete'):
                $connector->delete();
                Request::getFlashMessages()->addSuccessMessage(tr('The connector ":connector" has been deleted', [
                    ':connector' => $connector->getDisplayName()
                ]));

                Response::redirect();

            case tr('Lock'):
                $connector->lock();
                Request::getFlashMessages()->addSuccessMessage(tr('The connector ":connector" has been locked', [
                    ':connector' => $connector->getDisplayName()
                ]));

                Response::redirect();

            case tr('Unlock'):
                $connector->unlock();
                Request::getFlashMessages()->addSuccessMessage(tr('The connector ":connector" has been unlocked', [
                    ':connector' => $connector->getDisplayName()
                ]));

                Response::redirect();

            case tr('Undelete'):
                $connector->undelete();
                Request::getFlashMessages()->addSuccessMessage(tr('The connector ":connector" has been undeleted', [
                    ':connector' => $connector->getDisplayName()
                ]));

                Response::redirect();
        }

    } catch (IncidentsException|ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Request::getFlashMessages()->addMessage($e);
        $connector->forceApply();
    }
}


// Save button
if (!$connector->getReadonly()) {
    $save = Button::new()
        ->setValue(tr('Save'))
        ->setContent(tr('Save'));
}


// Buttons.
if (!$connector->isNew()) {
    if (!$connector->isReadonly()) {
        if ($connector->isDeleted()) {
            $delete = Button::new()
                ->setFloatRight(true)
                ->setMode(EnumDisplayMode::warning)
                ->setOutlined(true)
                ->setValue(tr('Undelete'))
                ->setContent(tr('Undelete'));

        } else {
            $delete = Button::new()
                ->setFloatRight(true)
                ->setMode(EnumDisplayMode::warning)
                ->setOutlined(true)
                ->setValue(tr('Delete'))
                ->setContent(tr('Delete'));

            if ($connector->isLocked()) {
                $lock = Button::new()
                    ->setFloatRight(true)
                    ->setMode(EnumDisplayMode::warning)
                    ->setValue(tr('Unlock'))
                    ->setContent(tr('Unlock'));

            } else {
                $lock = Button::new()
                    ->setFloatRight(true)
                    ->setMode(EnumDisplayMode::warning)
                    ->setValue(tr('Lock'))
                    ->setContent(tr('Lock'));
            }

            // Audit button.
            $audit = Button::new()
                ->setFloatRight(true)
                ->setMode(EnumDisplayMode::information)
                ->setAnchorUrl('/audit/meta+' . $connector->getMetaId() . '.html')
                ->setValue(tr('Audit'))
                ->setContent(tr('Audit'));
        }
    }

    // Test button.
    $test = Button::new()
        ->setFloatRight(true)
        ->setMode(EnumDisplayMode::information)
        ->setValue(tr('Test'))
        ->setContent(tr('Test'));
}


// Build the connector form
$connector_card = Card::new()
    ->setCollapseSwitch(true)
    ->setMaximizeSwitch(true)
    ->setTitle(tr('Edit connector :name', [':name' => $connector->getDisplayName()]))
    ->setContent($connector->getHtmlDataEntryFormObject()->render())
    ->setButtons(Buttons::new()
        ->addButton(isset_get($save))
        ->addButton(tr('Back'), EnumDisplayMode::secondary, UrlBuilder::getPrevious('/phoundation/databases/connectors/connectors.html'), true)
        ->addButton(isset_get($test))
        ->addButton(isset_get($audit))
        ->addButton(isset_get($delete))
        ->addButton(isset_get($lock))
        ->addButton(isset_get($impersonate)));


// Build relevant links
$relevant = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/phoundation/databases/databases.html') . '">' . tr('Manage databases') . '</a><br>');


// Build documentation
$documentation = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn(GridColumn::new()
        // The connector card and all additional cards
        ->addContent($connector_card->render())
        ->setSize(9)
        ->useForm(true))
    ->addColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setPageTitle(tr('Connector :connector', [':connector' => $connector->getDisplayName()]));
Response::setHeaderTitle(tr('Connector'));
Response::setHeaderSubTitle($connector->getDisplayName() . ($connector->isConfigured() ? ' [' . tr('Configured') . ']' : ''));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                                                 => tr('Home'),
    '/system-administration.html'                       => tr('System administration'),
    '/phoundation/databases.html'                       => tr('Databases'),
    '/phoundation/databases/connectors/connectors.html' => tr('Connectors'),
    ''                                                  => $connector->getDisplayName()
]));
