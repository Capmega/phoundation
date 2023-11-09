<?php

declare(strict_types=1);

use Phoundation\Accounts\Rights\Right;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\BreadCrumbs;
use Phoundation\Web\Html\Components\Button;
use Phoundation\Web\Html\Components\Buttons;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\DisplayMode;
use Phoundation\Web\Html\Enums\DisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page my/settings.php
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */


// Validate
$get = GetValidator::new()
    ->select('id')->isOptional()->isDbId()
    ->validate();


// Build the settings card
$user  = Session::getUser();
$form  = $user->getSettings()->getHtmlDataEntryForm();
$card  = Card::new()
    ->setTitle(tr('Edit data for right :name', [':name' => $user->getName()]))
    ->setContent($form->render())
    ->setButtons(Buttons::new()
        ->addButton(tr('Save'))
        ->addButton(tr('Back'), DisplayMode::secondary, UrlBuilder::getPrevious('/my/settings.html'), true)
        ->addButton(isset_get($delete))
        ->addButton(isset_get($audit)));


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/my/profile.html') . '">' . tr('My profile page') . '</a><br>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('In this settings page you may configure various details about how your account behaves on this platform. These settings are unique to your account alone');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($card, DisplaySize::nine, true)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('My settings'));
Page::setHeaderSubTitle($user->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                => tr('Home'),
    '/my/profile.html' => tr('My profile'),
    ''                 => tr('My settings')
]));
