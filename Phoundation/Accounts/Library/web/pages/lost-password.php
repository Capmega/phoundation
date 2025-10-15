<?php

/**
 * Page lost-password
 *
 * This page assists a user with recovering access to the system after they lost their password
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Core;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\Exception\CsrfValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\PhoException;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Pages\LostPasswordPage;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use PHPMailer\PHPMailer\PHPMailer;


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

        $user = User::new()->load(['email' => $post['email']]);

        if ($user->isLocked() or $user->isDeleted()) {
            // Yikes, this account is locked or deleted, no password request can be sent!
            Incident::new()
                    ->setType('Lost password request denied')
                    ->setSeverity(EnumSeverity::medium)
                    ->setTitle(tr('Cannot perform a lost password process for user ":user", this user account is locked or deleted', [
                        ':user' => $user->getLogId(),
                    ]))
                    ->setDetails([
                        'user'   => $user->getLogId(),
                        'status' => $user->getStatus(),
                    ])
                    ->setNotifyRoles('security')
                    ->save()
                    ->throw(AccessDeniedException::class);
        }

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

        // Build email
        $mail->Host    = '10.10.0.9';
        $mail->Port    = '25';
        $mail->Subject = tr('Lost password request');
        $mail->Body    = tr('Hello :user, this email is sent because you (or somebody) requested a password reset because they lost the password for this account.<br><br>If you did not request this, please notify your systems administrator.<br><br>If you did request this, please click :here to continue.<br><br>If you cannot click on the previous link, then please copy / paste the following link into a new browser page:<br>:alt', [
            ':user' => $user->getDisplayName(),
            ':here' => Anchor::new($key->getUrl(), tr('here')),
            ':alt'  => $key->getUrl(),
        ]);

//        $mail->SMTPDebug = SMTP::DEBUG_SERVER;

        if (Core::isProductionEnvironment()) {
            $mail->addBCC('sven@medinet.ca', 'Sven Olaf Oostenbrink');

        } else {
            $mail->addAddress($user->getEmail(), $user->getDisplayName());
        }

        $mail->setFrom(config()->getString('email.from.email'), config()->getString('email.from.name', 'Your Phoundation project') . ' (no-reply)' . (Core::isProductionEnvironment() ? null : ' (' . ENVIRONMENT . ')'));

        if (!$mail->send()) {
            throw new PhoException($mail->ErrorInfo);
        }

        // Register a security incident
        Incident::new()
                ->setSeverity(EnumSeverity::low)
                ->setType(tr('User lost password request'))
                ->setTitle(tr('A lost password request was sent to user ":user"', [
                    ':user' => $user->getLogId(),
                ]))
                ->setDetails([
                    'user'  => $user->getLogId(),
                ])
                ->setNotifyRoles('security')
                ->save();

        Response::getFlashMessagesObject()->addSuccess(tr('We sent a lost password email to the specified address if it exists. Please check your spam folder in case you haven\'t received it.'));

    } catch (CsrfValidationFailedException) {
        Response::getFlashMessagesObject()->addWarning(tr('The submission failed a security check, please try again'));

    } catch (ValidationFailedException) {
        Response::getFlashMessagesObject()->addWarning(tr('Please specify a valid email'));

    } catch (DataEntryNotExistsException | AccessDeniedException $e) {
        // Specified email does not exist, register a security incident
        Incident::new()
                ->setSeverity(EnumSeverity::low)
                ->setType(tr('Non existing user lost password request'))
                ->setTitle(tr('A lost password request was made for email ":email" but this account does not exist on this system for the environment ":environment"', [
                    ':email'       => array_get_safe($post, 'email'),
                    ':environment' => ENVIRONMENT,
                ]))
                ->setDetails([
                    'email'       => array_get_safe($post, 'email'),
                    'environment' => ENVIRONMENT,
                ])
                ->setNotifyRoles('security')
                ->save();
    }
}


// Set page meta data
Response::setPageTitle(tr('Your password has been updated, please go to the sign-in page in to continue...'));


// Render and return the page
return LostPasswordPage::new()->setGetData($get);
