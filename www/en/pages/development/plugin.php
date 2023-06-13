<?php

declare(strict_types=1);

use Phoundation\Core\Plugins\Plugin;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


// Validate GET
GetValidator::new()
    ->select('id')->isOptional()->isId()
    ->validate();

$plugin = Plugin::get($_GET['id']);

// Build the buttons
$buttons = Buttons::new()
    ->addButton('Submit')
    ->addButton('Back', DisplayMode::secondary, '/development/plugins.html', true);


// Build the plugin form
$plugin_card = Card::new()
    ->setHasCollapseSwitch(true)
    ->setTitle(tr('Edit data for Plugin :name', [':name' => $plugin->getName()]))
    ->setContent($plugin->getHtmlForm()->render())
    ->setButtons($buttons);


// Build the grid column with a form containing the plugin and roles cards
$column = GridColumn::new()
    ->addContent($plugin_card->render())
    ->setSize(9)
    ->useForm(true);


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/development/slow-pages.html') . '">' . tr('Slow pages') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/security/security.html') . '">' . tr('Security management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Build and render the grid
$grid = Grid::new()
    ->addColumn($column)
    ->addColumn($relevant->render() . $documentation->render(), 3);

echo $grid->render();

// Set page meta data
Page::setHeaderTitle(tr('Plugin'));
Page::setHeaderSubTitle($plugin->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                         => tr('Home'),
    '/development/plugins.html' => tr('Plugins'),
    ''                          => $plugin->getName()
]));
