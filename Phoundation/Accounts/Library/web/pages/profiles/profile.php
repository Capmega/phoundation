<?php

/**
 * Profile page
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Accounts\Users\User;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\Forms\Form;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;

// Get parameters
$get = GetValidator::new()
                   ->select('id')->isDbId()
                   ->validate();


// Get user
$user = User::new()->load($get['id']);


// Set page meta data
Response::setHeaderTitle(tr('Profile'));
Response::setHeaderSubTitle($user->getDisplayName());
Response::setBreadcrumbs([
    Anchor::new('/'                       , tr('Home')),
    Anchor::new('/profiles.html'          , tr('Profiles')),
    Anchor::new('/profiles/employees.html', tr('Employees')),
    Anchor::new(''                        , $user->getDisplayName()),
]);


if (Session::getUserObject()->hasAllRights(['accounts'])) {
    // Validate POST and submit
    if (Request::isPostRequestMethod()) {
        try {
            switch (PostValidator::new()->getSubmitButton()) {
                case tr('Lock'):
                    $user->lock();

                    Response::getFlashMessagesObject()->addSuccess(tr('The account for user ":user" has been locked', [
                        ':user' => $user->getDisplayName(),
                    ]));

                    Response::redirect();

                case tr('Unlock'):
                    $user->unlock();

                    Response::getFlashMessagesObject()->addSuccess(tr('The account for user ":user" has been unlocked', [
                        ':user' => $user->getDisplayName(),
                    ]));

                    Response::redirect();

                case tr('Impersonate'):
                    $user->impersonate();

                    Response::getFlashMessagesObject()->addSuccess(tr('You are now impersonating ":user"', [
                        ':user' => $user->getDisplayName(),
                    ]));

                    Response::redirect('root');
            }

        } catch (IncidentsException | ValidationFailedException | AccessDeniedException $e) {
            // Oops! Show validation errors and remain on page
            Response::getFlashMessagesObject()->addMessage($e);
            $user->forceApply();
        }
    }


    $edit = Button::new()
                  ->setMode(EnumDisplayMode::secondary)
                  ->setBlock(true)
                  ->setContent(tr('Edit'))
                  ->setContent(tr('Edit'))
                  ->setAnchorUrl('/accounts/user+' . $user->getId() . '.html');

    if ($user->canBeImpersonated()) {
        $impersonate = Button::new()
                             ->setBlock(true)
                             ->setMode(EnumDisplayMode::danger)
                             ->setContent(tr('Impersonate'))
                             ->setContent(tr('Impersonate'));
    }

    if ($user->canBeStatusChanged()) {
        if ($user->isLocked()) {
            $lock = Button::new()
                          ->setBlock(true)
                          ->setMode(EnumDisplayMode::warning)
                          ->setContent(tr('Unlock'))
                          ->setContent(tr('Unlock'));
        } else {
            $lock = Button::new()
                          ->setBlock(true)
                          ->setMode(EnumDisplayMode::warning)
                          ->setContent(tr('Lock'))
                          ->setContent(tr('Lock'));
        }
    }
}


// Build content
?>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">

                <!-- Profile Image -->
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <?= $user->getProfileImageObject()
                                         ->getHtmlImgObject()
                                             ->setId('profile-picture')
                                             ->addClasses('w100 img-circle')
                                             ->setAlt(tr('Profile picture for :user', [
                                                 ':user' => $user->getDisplayName()
                                             ]))
                                             ->render() ?>
                        </div>

                        <h3 class="profile-username text-center"><?= $user->getDisplayName() ?></h3>

                        <p class="text-muted text-center"><?= '-' ?></p>

                        <?=
//                        <ul class="list-group list-group-unbordered mb-3">
//                            <li class="list-group-item">
//                                <b>Followers</b> <a class="float-right">1,322</a>
//                            </li>
//                            <li class="list-group-item">
//                                <b>Following</b> <a class="float-right">543</a>
//                            </li>
//                            <li class="list-group-item">
//                                <b>Friends</b> <a class="float-right">13,287</a>
//                            </li>
//                        </ul>

                        Form::new()
                            ->setRequestMethod(EnumHttpRequestMethod::post)
                            ->setContent(Buttons::new()
                                    ->addButton(isset_get($edit))
                                    ->addButton(isset_get($lock))
                                    ->addButton(isset_get($impersonate))
                                    ->render())
                            ->render();
                        ?>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->

                <!-- About Me Box -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><?= tr('About Me') ?></h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <strong><i class="fas fa-book mr-1"></i> <?= tr('Contact information') ?></strong>

                        <p class="text-muted">
                            <?php

                            echo($user->getEmail() ? '<a href="mailto:' . $user->getEmail() . '">' . $user->getEmail() . '</a><br>' : null);

                            foreach ($user->getEmailsObject() as $email) {
                                echo '<a href="mailto:' . $email->getEmail() . '">' . $email->getEmail() . '</a><br>';
                            }

                            echo($user->getPhone() ? '<a href="tel:' . $user->getPhone() . '">' . $user->getPhone() . '</a><br>' : null);

                            foreach ($user->getPhonesObject() as $phone) {
                                echo '<a href="tel:' . $phone->getPhone() . '">' . $phone->getPhone() . '</a><br>';
                            }
                            ?>
                        </p>

                        <hr>

                        <strong><i class="fas fa-map-marker-alt mr-1"></i> Location</strong>

                        <p class="text-muted"><?= (($user->getCityObject() . $user->getCountryObject()) ? $user->getCityObject() . ', ' . $user->getCountryObject() : '-') ?></p>

                        <hr>

                        <strong><i class="fas fa-pencil-alt mr-1"></i> Skills</strong>

                        <p class="text-muted"></p>

                        <hr>

                        <strong><i class="far fa-file-alt mr-1"></i> Notes</strong>

                        <p class="text-muted">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam fermentum
                            enim neque.</p>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <?= Anchor::new('#activity', tr('Activity'))->addData('tab', 'toggle')->setClass('nav-link active') ?>
                            </li>
                            <li class="nav-item">
                                <?= Anchor::new('#timeline', tr('Timeline'))->addData('tab', 'toggle')->setClass('nav-link') ?>
                            </li>
                            <li class="nav-item">
                                <?= Anchor::new('#actions', tr('Actions'))->addData('tab', 'toggle')->setClass('nav-link') ?>
                            </li>
                        </ul>
                    </div><!-- /.card-header -->
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="active tab-pane" id="activity"></div>

                            <!-- /.tab-pane -->
                            <div class="tab-pane" id="timeline"></div>
                            <!-- /.tab-pane -->

                            <div class="tab-pane" id="actions">

                            </div>
                            <!-- /.tab-pane -->
                        </div>
                        <!-- /.tab-content -->
                    </div><!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div><!-- /.container-fluid -->
</section><!-- /.content -->
