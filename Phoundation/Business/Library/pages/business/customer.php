<?php

declare(strict_types=1);


use Phoundation\Business\Customers\Customer;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Components\Buttons\Buttons;
use Phoundation\Web\Html\Components\Img;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Validate GET
$get = GetValidator::new()
    ->select('id')->isOptional()->isDbId()
    ->validate();

$customer = Customer::new($get['id']);

// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
//        Customer::validate(PostValidator::new());
//
//        // Update customer
//        $customer = Customer::get($get['id'], exception: false);
//        $customer->apply()->save();
//
//        // Go back to where we came from
//        Request::getFlashMessages()->addFlashMessage(tr('Success'), tr('Customer ":customer" has been updated', [':customer' => $customer->getName()]));
//        Response::redirect('referer');

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Request::getFlashMessages()->addMessage($e);
        $customer->forceApply();
    }
}


// Build the buttons
$buttons = Buttons::new()
    ->addButton('Submit')
    ->addButton(tr('Back'), EnumDisplayMode::secondary, UrlBuilder::getPrevious('/accounts/customers.html'), true)
    ->addButton(tr('Audit'), EnumDisplayMode::information, '/audit/meta+' . $customer->getMetaId() . '.html', false, true);


// Build the customer form
$customer_card = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle(tr('Edit data for customer :name', [':name' => $customer->getName()]))
    ->setContent($customer->getHtmlDataEntryForm()->render())
    ->setButtons($buttons);


// Build the grid column with a form containing the customer and roles cards
$column = GridColumn::new()
    ->addContent($customer_card->render())
    ->setSize(9)
    ->useForm(true);


// Build profile picture card
$picture = Card::new()
    ->setTitle(tr('Customer profile picture'))
    ->setContent(Img::new()
        ->setSrc($customer->getPicture())
        ->setAlt(tr('Profile picture for :customer', [':customer' => $customer->getName()])));


// Build relevant links
$relevant = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/business/providers.html') . '">' . tr('Providers management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/business/companies.html') . '">' . tr('Companies management') . '</a>');


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
    ->addColumn($picture->render() . $relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();

// Set page meta data
Response::setHeaderTitle(tr('Customer'));
Response::setHeaderSubTitle($customer->getName());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                        => tr('Home'),
    '/business/customers.html' => tr('Customers'),
    ''                         => $customer->getName()
]));
