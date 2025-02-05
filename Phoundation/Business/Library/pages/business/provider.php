<?php

/**
 * Page provider
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Phoundation\Business
 */


declare(strict_types=1);

use Phoundation\Business\Providers\Provider;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\Img;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Validate GET
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId()
                   ->validate();

$provider = Provider::new()->loadOrThis($get['id']);

// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
//        Provider::validate(PostValidator::new());
//
//        // Update provider
//        $provider = Provider::get($get['id'], exception: false);
//        $provider->apply()->save();
//
//        // Go back to where we came from
//        Response::getFlashMessages()->addFlashMessage(tr('Success'), tr('Provider ":provider" has been updated', [':provider' => $provider->getName()]));
//        Response::redirect('referer');

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessagesObject()->addMessage($e);
        $provider->forceApply();
    }
}


// Build the buttons
$buttons = Buttons::new()
                  ->addButton('Submit')
                  ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::newPrevious('/accounts/providers.html'), true)
                  ->addButton(tr('Audit'), EnumDisplayMode::information, '/audit/meta+' . $provider->getMetaId() . '.html', false, true);

// Build the provider form
$provider_card = Card::new()
                     ->setCollapseSwitch(true)
                     ->setTitle(tr('Edit data for provider :name', [':name' => $provider->getName()]))
                     ->setContent($provider->getHtmlDataEntryFormObject()->render())
                     ->setButtons($buttons);


// Build the grid column with a form containing the provider and roles cards
$column = GridColumn::new()
                    ->addContent($provider_card->render())
                    ->setSize(9)
                    ->useForm(true);


// Build profile picture card
$picture = Card::new()
               ->setTitle(tr('Provider profile picture'))
               ->setContent(Img::new()
                               ->setSrc($provider->getImageFileObject())
                               ->setAlt(tr('Profile picture for :provider', [':provider' => $provider->getName()])));


// Build relevant links
$relevant = Card::new()
                ->setMode(EnumDisplayMode::info)
                ->setTitle(tr('Relevant links'))
                ->setContent('<a href="' . Url::new('/business/customers.html')->makeWww() . '">' . tr('Customers management') . '</a><br>
                         <a href="' . Url::new('/business/companies.html')->makeWww() . '">' . tr('Companies management') . '</a>');


// Build documentation
$documentation = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Documentation'))
                     ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Render and return the page grid
$grid = Grid::new()
            ->addGridColumn($column)
            ->addGridColumn($picture->render() . $relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();

// Set page meta data
Response::setHeaderTitle(tr('Provider'));
Response::setHeaderSubTitle($provider->getDisplayName());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                        => tr('Home'),
    '/business/providers.html' => tr('Providers'),
    ''                         => $provider->getDisplayName(),
]));
