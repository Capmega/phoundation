<?php

use Phoundation\Accounts\Users\User;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Html\Components\Buttons\Button;
use Phoundation\Web\Html\Components\Buttons\Buttons;
use Phoundation\Web\Html\Components\Forms\Form;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Profile page
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Get parameters
$get = GetValidator::new()
    ->select('id')->isDbId()
    ->validate();


// Get user
$user = User::get($get['id']);


// Set page meta data
Page::setHeaderTitle(tr('Profile'));
Page::setHeaderSubTitle($user->getDisplayName());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                        => tr('Home'),
    '/profiles.html'           => tr('Profiles'),
    '/profiles/employees.html' => tr('Employees'),
    ''                         => $user->getDisplayName()
]));


if (Session::getUser()->hasAllRights(['accounts'])) {
// Validate POST and submit
    if (Page::isPostRequestMethod()) {
        try {
            switch (PostValidator::getSubmitButton()) {
                case tr('Lock'):
                    $user->lock();
                    Page::getFlashMessages()->addSuccessMessage(tr('The account for user ":user" has been locked', [
                        ':user' => $user->getDisplayName()
                    ]));

                    Page::redirect();

                case tr('Unlock'):
                    $user->unlock();
                    Page::getFlashMessages()->addSuccessMessage(tr('The account for user ":user" has been unlocked', [
                        ':user' => $user->getDisplayName()
                    ]));

                    Page::redirect();

                case tr('Impersonate'):
                    $user->impersonate();
                    Page::getFlashMessages()->addSuccessMessage(tr('You are now impersonating ":user"', [
                        ':user' => $user->getDisplayName()
                    ]));

                    Page::redirect('root');
            }

        } catch (IncidentsException|ValidationFailedException $e) {
            // Oops! Show validation errors and remain on page
            Page::getFlashMessages()->addMessage($e);
            $user->forceApply();
        }
    }


    $edit = Button::new()
        ->setMode(EnumDisplayMode::secondary)
        ->setValue(tr('Edit'))
        ->setContent(tr('Edit'))
        ->setAnchorUrl('/accounts/user+' . $user->getId() . '.html');

    if ($user->canBeImpersonated()) {
        $impersonate = Button::new()
            ->setFloatRight(true)
            ->setMode(EnumDisplayMode::danger)
            ->setValue(tr('Impersonate'))
            ->setContent(tr('Impersonate'));
    }

    if ($user->canBeStatusChanged()) {
        if ($user->isLocked()) {
            $lock = Button::new()
                ->setFloatRight(true)
                ->setMode(EnumDisplayMode::warning)
                ->setValue(tr('Unlock'))
                ->setContent(tr('Unlock'));

        } else {
            $lock = Button::new()
                ->setFloatRight(true)
                ->setMode(EnumDisplayMode::warning)
                ->setValue(tr('Lock'))
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
                <?= Session::getUser()->getPicture()
                    ->getHtmlElement()
                    ->setSrc(UrlBuilder::getImg('img/profiles/default.png'))
                    ->setClass('profile-user-img img-fluid img-circle')
                    ->setAlt(tr('Profile picture for :user', [':user' => Html::safe(Session::getUser()->getDisplayName())]))
                    ->render() ?>
            </div>

            <h3 class="profile-username text-center"><?= $user->getDisplayName() ?></h3>

            <p class="text-muted text-center"><?= '-' ?></p>

            <ul class="list-group list-group-unbordered mb-3">
              <li class="list-group-item">
                <b>Followers</b> <a class="float-right">1,322</a>
              </li>
              <li class="list-group-item">
                <b>Following</b> <a class="float-right">543</a>
              </li>
              <li class="list-group-item">
                <b>Friends</b> <a class="float-right">13,287</a>
              </li>
            </ul>

            <a href="#" class="btn btn-primary btn-block"><b>Follow</b></a>
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

                    echo ($user->getEmail() ? '<a href="mailto:' . $user->getEmail() . '">' . $user->getEmail() . '</a><br>' : null);

                    foreach ($user->getEmails() as $email) {
                        echo '<a href="mailto:' . $email->getEmail() . '">' . $email->getEmail() . '</a><br>';
                    }

                    echo ($user->getPhone() ? '<a href="tel:' . $user->getPhone() . '">' . $user->getPhone() . '</a><br>' : null);

                    foreach ($user->getPhones() as $phone) {
                        echo '<a href="tel:' . $phone->getPhone() . '">' . $phone->getPhone() . '</a><br>';
                    }
                ?>
            </p>

            <hr>

            <strong><i class="fas fa-map-marker-alt mr-1"></i> Location</strong>

            <p class="text-muted"><?= (($user->getCity() . $user->getCountry()) ? $user->getCity() . ', ' . $user->getCountry() : '-') ?></p>

            <hr>

            <strong><i class="fas fa-pencil-alt mr-1"></i> Skills</strong>

            <p class="text-muted">
            </p>

            <hr>

            <strong><i class="far fa-file-alt mr-1"></i> Notes</strong>

            <p class="text-muted">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam fermentum enim neque.</p>
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
              <li class="nav-item"><a class="nav-link active" href="#activity" data-toggle="tab"><?= tr('Activity') ?></a></li>
              <li class="nav-item"><a class="nav-link" href="#timeline" data-toggle="tab"><?= tr('Timeline') ?></a></li>
              <li class="nav-item"><a class="nav-link" href="#actions" data-toggle="tab"><?= tr('Actions') ?></a></li>
            </ul>
          </div><!-- /.card-header -->
          <div class="card-body">
            <div class="tab-content">
              <div class="active tab-pane" id="activity">
              </div>

              <!-- /.tab-pane -->
              <div class="tab-pane" id="timeline">
              </div>
              <!-- /.tab-pane -->

              <div class="tab-pane" id="actions">
                  <?=
                    Form::new()
                        ->setMethod('post')
                        ->setContent('   <div class="form-group row">' .
                                                     Buttons::new()
                                                         ->addButton(isset_get($edit))
                                                         ->addButton(isset_get($lock))
                                                         ->addButton(isset_get($impersonate))
                                                         ->render() . '
                                                </div>')
                        ->render();
                  ?>
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
</section>
<!-- /.content -->
