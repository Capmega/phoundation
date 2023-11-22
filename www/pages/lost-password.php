<?php

use Phoundation\Accounts\Users\User;
use Phoundation\Core\Core;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Security\Incidents\Severity;
use Phoundation\Utils\Config;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;
use PHPMailer\PHPMailer\PHPMailer;


// Only show sign-in page if we're a guest user
if (!Session::getUser()->isGuest()) {
    Page::redirect('prev', 302, reason_warning: tr('Lost password page is only available to guest users'));
}


// Is email specified by URL?
$get = GetValidator::new()
    ->select('email')->isOptional()->isEmail()
    ->select('redirect')->isOptional()->isUrl()
    ->validate();


// Validate sign in data and sign in
if (Page::isPostRequestMethod()) {
    try {
        $post = PostValidator::new()
            ->select('email')->isEmail()
            ->validate();

        $user = User::get($post['email'], 'email');

        if ($user->isLocked() or $user->isDeleted()) {
            // Yikes, this cannot be impersonated!
            Incident::new()
                ->setType('Lost password request denied')
                ->setSeverity(Severity::medium)
                ->setTitle(tr('Cannot perform a lost password process for user ":user", this user account is locked or deleted', [
                    ':user' => $user->getLogId(),
                ]))
                ->setDetails([
                    'user'   => $user->getLogId(),
                    'status' => $user->getStatus(),
                ])
                ->notifyRoles('security')
                ->save()
                ->throw(AccessDeniedException::class);
        }

        $key  = $user->getSigninKey()->generate(UrlBuilder::getWww('/update-lost-password.html'));

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
            ':here' => tr('<a href=":url">here</a>', [':url' => $key->getUrl()]),
            ':alt'  => $key->getUrl()
        ]);

        $mail->Subject = tr('[:project] Lost password request', [
            ':project' => Config::getString('project.name', 'Phoundation') . ((ENVIRONMENT === 'production') ? ' - ' . strtoupper(ENVIRONMENT) : '')
        ]);

//        $mail->SMTPDebug = SMTP::DEBUG_SERVER;

        if (Core::isProduction()) {
            $mail->addBCC('sven@medinet.ca', 'Sven Olaf Oostenbrink');
        } else {
            $mail->addAddress($user->getEmail(), $user->getDisplayName());
        }

        $mail->setFrom('no-reply@medinet.ca', 'Medinet no-reply');

        if (!$mail->send()) {
            throw new \Phoundation\Exception\Exception($mail->ErrorInfo);
        }

        // Register a security incident
        Incident::new()
            ->setSeverity(Severity::low)
            ->setType(tr('User lost password request'))
            ->setTitle(tr('A lost password request was sent to user ":user"', [
                ':user' => Session::getUser()->getLogId()
            ]))
            ->setDetails([
                ':user' => Session::getUser()->getLogId()
            ])
            ->save();

        Page::getFlashMessages()->addSuccessMessage(tr('We sent a lost password email to the specified address if it exists'));

    } catch (ValidationFailedException) {
        Page::getFlashMessages()->addWarningMessage(tr('Please specify a valid email'));

    } catch (DataEntryNotExistsException|AccessDeniedException $e) {
        // Specified email does not exist. Just ignore it because we don't want to give away if the email exists or
        // not
    }
}


// This page will build its own body
Page::setBuildBody(false);

?>
<?= Page::getFlashMessages()->render() ?>
    <body class="hold-transition login-page" style="background: url(<?= UrlBuilder::getImg('img/backgrounds/' . Page::getProjectName() . '/lost-password.jpg') ?>); background-position: center; background-repeat: no-repeat; background-size: cover;">
    <div class="login-box">
        <!-- /.login-logo -->
        <div class="card card-outline card-info">
            <div class="card-header text-center">
              <a href="<?= Config::getString('project.customer-url', 'https://phoundation.org'); ?>" class="h1"><?= Config::getString('project.owner.label', '<span>Phoun</span>dation'); ?></a>
            </div>
            <div class="card-body">
                <p class="login-box-msg"><?= tr('Please provide your email address and we will send you a link where you can re-establish your password') ?></p>

                <form action="<?= UrlBuilder::getWww() ?>" method="post">
                    <?php
                    if (Session::supports('email')) {
                        ?>
                        <div class="input-group mb-3">
                            <input type="email" name="email" id="email" class="form-control" placeholder="<?= tr('Email address') ?>"<?= isset_get($get['email']) ? 'value="' . $get['email'] . '"' : '' ?>>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <!-- /.col -->
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-block"><?= tr('Request a new password') ?></button>
                            </div>
                            <!-- /.col -->
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <a class="btn btn-outline-secondary btn-block" href="<?= UrlBuilder::getWww('/sign-in.html')->addQueries(isset_get($get['email']) ? 'email=' . $get['email'] : '', isset_get($get['redirect']) ? 'redirect=' . $get['redirect'] : '') ?>"><?= tr('Back to sign in') ?></a>
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


// Set page meta data
Page::setPageTitle(tr('Request a new password'));
