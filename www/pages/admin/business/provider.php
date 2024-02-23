<?php

declare(strict_types=1);


use Phoundation\Business\Providers\Provider;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Img;
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

$provider = Provider::get($_GET['id']);

// Validate POST and submit
if (Page::isPostRequestMethod()) {
    try {
//        Provider::validate(PostValidator::new());
//
//        // Update provider
//        $provider = Provider::get($_GET['id']);
//        $provider->apply()->save();
//
//        // Go back to where we came from
//        Page::getFlashMessages()->add(tr('Success'), tr('Provider ":provider" has been updated', [':provider' => $provider->getName()]), 'success');
//        Page::redirect('referer');

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->add($e);
        $provider->forceApply();
    }
}


// Build the buttons
$buttons = Buttons::new()
    ->addButton('Submit')
    ->addButton(tr('Back'), 'secondary', '/business/providers.html', true)
    ->addButton(tr('Audit'), 'green', '/audit/meta-' . $provider->getMetaObject() . '.html', false, true);

// Build the provider form
$provider_card = Card::new()
    ->setHasCollapseSwitch(true)
    ->setTitle(tr('Edit data for provider :name', [':name' => $provider->getName()]))
    ->setContent($provider->getHtmlForm()->render())
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
        ->setSrc($provider->getPicture())
        ->setAlt(tr('Profile picture for :provider', [':provider' => $provider->getName()])));


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/business/customers.html') . '">' . tr('Customers management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/business/companies.html') . '">' . tr('Companies management') . '</a>');


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
    ->addColumn($picture->render() . $relevant->render() . $documentation->render(), 3);

echo $grid->render();

// Set page meta data
Page::setHeaderTitle(tr('Provider'));
Page::setHeaderSubTitle($provider->getName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                        => tr('Home'),
    '/business/providers.html' => tr('Providers'),
    ''                         => $provider->getName()
]));
