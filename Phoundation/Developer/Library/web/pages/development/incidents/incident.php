<?php

declare(strict_types=1);


use Phoundation\Data\Validator\GetValidator;
use Phoundation\Developer\Incidents\Incident;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;


// Validate GET
$get = GetValidator::new()
    ->select('id')->isOptional()->isDbId()
    ->validate();

$incident = Incident::get($get['id']);

// Build the buttons
$buttons = Buttons::new()
    ->addButton('Submit')
    ->addButton(tr('Back'), EnumDisplayMode::secondary, UrlBuilder::getPrevious('/accounts/incidents.html'), true);


// Build the incident form
$incident_card = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle(tr('Edit data for Incident :name', [':name' => $incident->getTitle()]))
    ->setContent($incident->getHtmlDataEntryFormObject()->render())
    ->setButtons($buttons);


// Build the grid column with a form containing the incident and roles cards
$column = GridColumn::new()
    ->addContent($incident_card->render())
    ->setSize(9)
    ->useForm(true);


// Build relevant links
$relevant = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/development/slow-pages.html') . '">' . tr('Slow pages') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/security/security.html') . '">' . tr('Security management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($column)
    ->addColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();

// Set page meta data
Response::setHeaderTitle(tr('Incident'));
Response::setHeaderSubTitle($incident->getTitle());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                           => tr('Home'),
    '/development/incidents.html' => tr('Incidents'),
    ''                            => $incident->getTitle()
]));
