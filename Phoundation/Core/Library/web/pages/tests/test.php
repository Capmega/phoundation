<?php

/**
 * Page index
 *
 * This is the default page redirected to from sign-in. It is useful as a dashboard, show messages, etc
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Html\Components\Forms\Form;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Requests\Response;


// Set page meta-data
Response::setPageTitle(tr('Main test page'));
Response::setHeaderTitle(tr('Main test page'));
Response::setDescription(tr(''));
Response::setBreadcrumbs([
    Breadcrumb::new('/'                , tr('Home')),
    Breadcrumb::new('/tests/tests.html', tr('Tests')),
    Breadcrumb::new(''                 , tr('Main test page')),
]);


show(GetValidator::new()->getSource());
show(PostValidator::new()->getSource());
GetValidator::new()->validate();


//return '<form method="post" action="/en/tests/test.html">
//<button type="submit" name="submit-button" value="godverdomme">Submit!</button>
//</form>';

return Form::new()->setContent(Button::new()->setRequireKeysToEnable(['ctrl', 'alt'], 'blergh')->setContent('test with lock')->setValue(1) . ' ' .
                               Button::new()->setContent('test without lock')->setValue(2) . ' ' );
