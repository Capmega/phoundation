<?php

/**
 * Page magic-sign-in
 *
 * This page allows users to sign in using a sign-key through their email
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Core;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Developer\Project\Project;
use Phoundation\Exception\PhoException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Csrf;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use PHPMailer\PHPMailer\PHPMailer;

throw new UnderConstructionException();

// Only show sign-in page if we're a guest user
if (!Session::getUserObject()->isGuest()) {
    Response::redirect('prev', 302, reason_warning: tr('Lost password page is only available to guest users'));
}


// Is email specified by URL?
$get = GetValidator::new()
                   ->select('email')->isOptional()->isEmail()
                   ->select('redirect')->isOptional()->isUrl()
                   ->validate();


// Validate sign in data and sign in
if (Request::isPostRequestMethod()) {
    try {
        $post = PostValidator::new()
                             ->select('email')->isEmail()
                             ->validate();

        try {
            $user = User::new()->load($post['email']);
            $key  = $user->getSigninKey()->generate(Url::new('/update-lost-password.html')->makeWww());

            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->isHTML(true);

//        // Setup email host configuration
//        $mail->Host = "smtp.gmail.com";


//        $mail->SMTPAuth = true;


//        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;


//        $mail->Username = "so.oostenbrink@gmail.com";


//        $mail->Password = 'kzusumqvavzfmyml';


//        $mail->Port = "587";

            $mail->Host = "10.10.0.9";
            $mail->Port = "25";

            // Build email
            $mail->Body = tr('Hello :user, this email is sent because you (or somebody) requested a password reset because they lost the password for this account.<br><br>If you did not request this, please notify your systems administrator.<br><br>If you did request this, please click :here to continue.<br><br>If you cannot click on the previous link, then please copy / paste the following link into a new browser page:<br>:alt', [
                ':user' => $user->getDisplayName(),
                ':here' => Anchor::new($key->getUrl(), tr('here')),
                ':alt'  => $key->getUrl(),
            ]);

            $mail->Subject = tr('[:project] Lost password request', [
                ':project' => Project::getHumanReadableFullName() . ((ENVIRONMENT === 'production') ? ' - ' . strtoupper(ENVIRONMENT) : ''),
            ]);

//        $mail->SMTPDebug = SMTP::DEBUG_SERVER;

            if (Core::isProductionEnvironment()) {
                $mail->addBCC('sven@phoundation.org', 'Sven Olaf Oostenbrink');
            } else {
                $mail->addAddress($user->getEmail(), $user->getDisplayName());
            }

            $mail->setFrom('no-reply@phoundation.org', 'Phoundation no-reply');

            if (!$mail->send()) {
                throw new PhoException($mail->ErrorInfo);
            }

        } catch (DataEntryNotExistsException $e) {
            // Specified email does not exist. Just ignore it because we don't want to give away if the email exists or
            // not
        }

        Response::getFlashMessagesObject()->addSuccess(tr('We sent a lost password email to the specified address if it exists'));

    } catch (ValidationFailedException) {
        Response::getFlashMessagesObject()->addWarning(tr('Please specify a valid email and password'));

    } catch (AuthenticationException) {
        Response::getFlashMessagesObject()->addWarning(tr('The specified email or password was incorrect'));
    }
}


// Set page meta-data
Response::setRenderMainWrapper(false);
Response::setPageTitle(tr('Request a new password'));


// This page will build its own body
?>
<?= Response::getFlashMessagesObject()->render() ?>
    <body class="hold-transition login-page"
          style="background: url(<?= Url::new('backgrounds/lost-password.jpg')->makeImg() ?>); background-position: center; background-repeat: no-repeat; background-size: cover;">
    <div class="login-box">
        <!-- /.login-logo -->
        <div class="card card-outline card-info">
            <div class="card-header text-center">
                <?=
                    Anchor::new(Project::getOwnerUrl())
                          ->setContent(Project::getOwnerLabel(), false)
                          ->setClass('h1')
                ?>
            </div>
            <div class="card-body">
                <p class="login-box-msg"><?= tr('Please provide your email address and we will send you a link which you can use to login directly') ?></p>

                <form action="<?= Url::newCurrent() ?>" method="post">
                    <?php Csrf::getHiddenElement() ?>
                    <?php
                    if ($o_component->getEnabled('email')) {
                        ?>
                        <div class="input-group mb-3">
                            <input type="email" name="email" id="email" class="form-control"
                                   placeholder="<?= tr('Email address') ?>"<?= array_get_safe($get, 'email') ? 'value="' . $get['email'] . '"' : '' ?>>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <!-- /.col -->
                            <div class="col-12">
                                <button type="submit"
                                        class="btn btn-primary btn-block"><?= tr('Request a new password') ?></button>
                            </div>
                            <!-- /.col -->
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <?=
                                    Anchor::new(Url::new('/sign-in.html')->makeWww()->addRedirect(array_get_safe($get, 'redirect'))->addQuery(array_get_safe($get, 'email'), 'email'))
                                          ->setContent(tr('Back to sign in'))
                                          ->setClass('btn btn-outline-secondary btn-block')
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </form>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
    </body>
<?php
