<?php

use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\ButtonType;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Plugins\Medinet\Timesheet\Timesheets;
use Plugins\Medinet\Timesheet\Timesheet;


/**
 * Today's timesheet page
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Load all timesheets for today
$timesheets = Timesheets::getToday();


// Process submits
if (Page::isPostRequestMethod()) {
    try {
        // Save the new timesheet, if any
        $modified = Timesheet::get()->apply(false)->save();

        // Save all existing timesheet entries
        foreach ($timesheets as $timesheet) {
            $modified = ($modified or $timesheet->setFieldPrefix($timesheet->getId() . '_')->apply(false)->save());
        }

        if ($modified) {
            Page::getFlashMessages()->add(tr('Success!'), tr('Your timesheet has been updated'), DisplayMode::success);
        } else {
            Page::getFlashMessages()->add(tr('Warning'), tr('Your timesheet was not modified'), DisplayMode::warning);
        }

        Page::redirect();

    } catch (DataEntryNotExistsException|ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page. pre-fill timesheets with invalid data
        Page::getFlashMessages()->add($e);

        foreach ($timesheets as $timesheet) {
            $timesheet->setFieldPrefix($timesheet->getId() . '_')->forceApply();
        }
    }
}

// Build the buttons
$buttons = Buttons::new()
    ->addButton('Submit', DisplayMode::primary  , ButtonType::submit)
    ->addButton('Back'  , DisplayMode::secondary, '/timesheets/my.html', true);


// Get timesheet data and start content with one empty entry at the top
$timesheet       = Timesheets::new();
$timesheet_cards = [];
$content         = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle(tr('New entry'))
    ->setContent(Timesheet::new()->getHtmlForm()->render())
    ->setButtons($buttons)
    ->render();

foreach ($timesheets as $timesheet) {
    $description = $timesheet->getDescription();
    $content .= Card::new()
        ->setCollapseSwitch(true)
        ->setCollapsed(true)
        ->setTitle(tr(':time [:title]', [':time' => Strings::untilReverse(Strings::from($timesheet->getStart(), ' '), ':'), ':title' => ($description ? ' (' . Strings::truncate($description, 48) . ')' : '-')]))
        ->setContent($timesheet->setFieldPrefix($timesheet->getId() . '_')->getHtmlForm()->render())
        ->setButtons($buttons)
        ->render();
}


//// Build the timesheet entries
//foreach ($timesheet->getList() as $entry) {
//// Build the timesheet card
//    $timesheet_cards[] = Card::new()
//        ->setHa                                     sCollapseSwitch(true)
//        ->setTitle(tr('Edit timesheet for :name', [':name' => Session::getRealUser()->getDisplayName()]))
//        ->setContent($entry->getHtmlForm()->render())
//        ->setButtons($buttons)
//        ->render();
//}


// Build the grid column with a form containing the timesheet and roles cards
$column = GridColumn::new()
    ->addContent($content . implode('', $timesheet_cards))
    ->setSize(9)
    ->useForm(true);


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/timesheets/today.html') . '">' . tr('Today\'s timesheet') . '</a><br>
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
Page::setPageTitle(tr('My timesheet for today'));
Page::setHeaderTitle(tr('My timesheet for today'));
Page::setHeaderSubTitle(tr('(under development)'));
Page::setDescription(tr(''));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                    => tr('Home'),
    '/timesheets/my.html'  => tr('My timesheets'),
    ''                     => tr('Today')
]));
