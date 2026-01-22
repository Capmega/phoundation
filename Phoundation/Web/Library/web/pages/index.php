<?php

/**
 * Page index
 *
 * This is the default page redirected to from sign-in. It is useful as a dashboard, show messages, etc
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Utils\Enums\EnumModifierKeys;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Requests\Response;


// This page accepts no GET parameters
GetValidator::new()->validate();


// Set page meta-data
Response::setPageTitle(tr('Dashboard'));
Response::setHeaderTitle(tr('Dashboard'));
Response::setHeaderSubTitle(tr('(:user)', [':user' => Session::getUserObject()->getDisplayName()]));
Response::setDescription(tr(''));
Response::setBreadcrumbs([
    Breadcrumb::new('/', tr('Home')),
    Breadcrumb::new('' , tr('Dashboard')),
]);


return Button::new()
             ->setContent('hello!')
             ->setTitle('This is the real title!')
             ->setRequireKeysToEnable([EnumModifierKeys::ctrl, EnumModifierKeys::alt], 'blergh') . Script::new('
console.log("zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz");
console.log(window.phoundation);

             
             window.phoundation.addModifierkeyDownCallback("ctrl alt", function () {
                $buttons = $(".button-disable-click");
                $buttons.prop("title", $buttons.data("tooltip"))
                        .prop("disabled", false)
                        .removeClass("disabled");
console.log("ctrl alt DOWN!");                        
             });
             
             window.phoundation.addModifierkeyUpCallback("ctrl alt", function () {
                $buttons = $(".button-disable-click");
                $buttons.prop("tooltip", $buttons.data("require-keys-tooltip"))
                        .prop("disabled", true)
                        .addClass("disabled");
console.log("ctrl alt UP!");                        
             });
             
console.log("Registered modifiers: ");
console.log(window.phoundation.getModifierkeyDownCallbacks("ctrl alt"));
console.log(window.phoundation.getModifierkeyUpCallbacks("ctrl alt"));
             
             ')->setJavascriptWrapper(EnumJavascriptWrappers::window);

