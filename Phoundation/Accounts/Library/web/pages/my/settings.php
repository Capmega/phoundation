<?php

declare(strict_types=1);

use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;


/**
 * Page my/settings.php
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


// Validate
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId()
                   ->validate();


// Build the settings card
$user = Session::getUser();
$form = $user->getSettings()->getHtmlDataEntryForm();
$card = Card::new()
            ->setTitle(tr('Edit data for right :name', [':name' => $user->getName()]))
            ->setContent($form->render())
            ->setButtons(Buttons::new()
                                ->addButton(tr('Save'))
                                ->addButton(tr('Back'), EnumDisplayMode::secondary, UrlBuilder::getPrevious('/my/settings.html'), true)
                                ->addButton(isset_get($delete))
                                ->addButton(isset_get($audit)));


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . UrlBuilder::getWww('/my/profile.html') . '">' . tr('My profile page') . '</a><br>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('In this settings page you may configure various details about how your account behaves on this platform. These settings are unique to your account alone');


// Build and render the page grid
$grid = Grid::new()
            ->addColumn($card, EnumDisplaySize::nine, true)
            ->addColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('My settings'));
Response::setHeaderSubTitle($user->getName());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
                                                           '/'                => tr('Home'),
                                                           '/my/profile.html' => tr('My profile'),
                                                           ''                 => tr('My settings'),
                                                       ]));
