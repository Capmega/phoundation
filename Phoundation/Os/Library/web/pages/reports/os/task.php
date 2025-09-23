<?php

/**
 * Page reports/task
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Os\Tasks\Task;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


// Validate
$get = GetValidator::new()
                   ->select('id')->isDbId()
                   ->select('date_range')->isOptional()->isDateRange()
                   ->validate();


// Build the page content
$o_task = Task::new()->load($get['id']);
$o_card = Card::new()
              ->setTitle($o_task->getName())
              ->setMaximizeSwitch(true)
              ->setContent($o_task->getHtmlDataEntryFormObject())
              ->setButtonsObject(Buttons::new()->addButton(
                  tr('Back'),
                  EnumDisplayMode::secondary,
                  Url::newPrevious('/reports/security/incidents.html')->addQueries(
                      $get['date_range'] ? 'date_range=' . $get['date_range'] : ''
                  ),
                  true));


// Build relevant links
$o_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new(Url::new('/reports/security/authentications.html')->makeWww()->addQueries($get['date_range'] ? 'date_range=' . $get['date_range'] : ''), tr('Authentications management')) .
                                    AnchorBlock::new(Url::new('/reports/security/incidents.html')->makeWww()->addQueries($get['date_range'] ? 'date_range=' . $get['date_range'] : ''), tr('Incidents management')) .
                                    hr(AnchorBlock::new(Url::new('/accounts/users.html')->makeWww(), tr('Users management')) .
                                       AnchorBlock::new(Url::new('/accounts/roles.html')->makeWww(), tr('Roles management')) .
                                       AnchorBlock::new(Url::new('/accounts/rights.html')->makeWww(), tr('Rights management'))));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('This page shows the details of a single specific task. The information on this page cannot be modified');


// Set page meta data
$o_url = Url::new('/reports/os/tasks.html')->makeWww()->addQueries(
    $get['date_range'] ? 'date_range=' . $get['date_range'] : ''
);

Response::setHeaderTitle(tr('Task'));
Response::setHeaderSubTitle($o_task->getDisplayId());
Response::setBreadcrumbs([
    Breadcrumb::new('/'             , tr('Home')),
    Breadcrumb::new('/security.html', tr('Security')),
    Breadcrumb::new($o_url          , tr('Task management')),
    Breadcrumb::new(''              , $o_task->getDisplayId()),
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_card                                 , EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
