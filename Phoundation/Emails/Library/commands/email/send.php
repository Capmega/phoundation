<?php

/**
 * Command email send
 *
 * This command can send out emails
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Accounts\Config\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Accounts\Users\User;
use Phoundation\Accounts\Users\Users;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Emails\Email;
use Phoundation\Notifications\Exception\NotificationsException;
use PHPMailer\PHPMailer\PHPMailer;


CliDocumentation::setAutoComplete([
    'arguments' => [
        '-f,--from' => function ($word) { return Users::new()->load()->keepMatchingKeys($word)->limitAutoComplete(); },
        '-t,--to'   => function ($word) { return Users::new()->load()->keepMatchingKeys($word)->limitAutoComplete(); },
        '--bcc'     => function ($word) { return Users::new()->load()->keepMatchingKeys($word)->limitAutoComplete(); },
        '--cc'      => function ($word) { return Users::new()->load()->keepMatchingKeys($word)->limitAutoComplete(); },
        '-s,--to'   => true,
        '-b,--body' => true,
        '--timeout' => true,
        '-h,--html' => false,
        '--host'    => true,
        '--port'    => true,
    ],
]);

CliDocumentation::setUsage('./pho email send');

CliDocumentation::setHelp('This command can send out emails


ARGUMENTS


-b,--body STRING                        The email body

-h,--html                               If specified, the email body will be sent as HTML

-s,--subject STRING                     The subject line for the email

-t,--to EMAIL                           The email address of the user to which this mail should be sent

--timeout TIMEOUT [5]                   The amount of seconds until the send command will timeout


OPTIONAL ARGUMENTS


[--cc EMAIL [, EMAIL, EMAIL, ...]]      If specified, adds a CC for each specified email

[--bcc EMAIL [, EMAIL, EMAIL, ...]]     If specified, adds a BCC for each specified email

[-f,--from EMAIL]                       The email address of the user from which this mail should be sent

[--hostname HOST]                       If specified, will use the specified host instead of the default configured host

[--port PORT]                           If specified, will use the specified host port instead of the default configured port');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-a,--attachments', true)->isOptional()->sanitizeForceArray()->forEachField()->sanitizeFile()
                     ->select('-f,--from', true)->isOptional()->isEmail()
                     ->select('-t,--to', true)->isEmail()
                     ->select('-c,--cc', true)->isOptional()->sanitizeForceArray()->forEachField()->isEmail()
                     ->select('--bcc', true)->isOptional()->sanitizeForceArray()->forEachField()->isEmail()
                     ->select('-s,--subject', true)->hasMaxCharacters(255)
                     ->select('-b,--body', true)->hasMaxCharacters(16_777_200)
                     ->select('-h,--html')->isOptional()->isBoolean()
                     ->select('--hostname')->isOptional(Email::getDefaultHostname())->isDomainOrIp()
                     ->select('--port')->isOptional(Email::getDefaultPort())->isInteger()->isBetween(1, 65535)
                     ->select('-t,--timeout')->isOptional()->isInteger()->isPositive()->isLessThan(86400)
                     ->validate();


// TODO Use the Email library for sending emails, once its ready
//Email::new()
//    ->addFrom($argv['from'])
//    ->addTo($argv['to'])
//    ->setSubject($argv['subject'])
//    ->setBody($argv['body'])
//    ->send();


// Initialize "to"
$to   = User::new()->load($argv['to']);


// Initialize PHPMailer object
$mail          = new PHPMailer();
$mail->Host    = Email::getDefaultHostname();
$mail->Port    = Email::getDefaultPort();
$mail->Subject = $argv['subject'];
$mail->Body    = $argv['body'];
$mail->Timeout = Email::getDefaultTimeout();;

$mail->isSMTP();
$mail->isHTML($argv['html']);
$mail->addAddress(Email::getOverrideEmail() ?? $to->getEmail(), $to->getDisplayName());


// Optionally, add CC fields (But ONLY if emails are not overridden)
if ($argv['cc']) {
    foreach ($argv['cc'] as $cc) {
        $_cc = User::new($cc);

        // If an override email is set, adding them as CC only sends duplicate mails to the override email.
        if (empty(Email::getOverrideEmail($_cc))) {
            $mail->addCC($_cc->getEmail(), $_cc->getDisplayName());
        }
    }
}


// Optionally, add BCC fields (But ONLY if emails are not overridden)
if ($argv['cc']) {
    foreach ($argv['cc'] as $bcc) {
        $_bcc = User::new($bcc);

        // If an override email is set, adding them as BCC only sends duplicate mails to the override email.
        if (empty(Email::getOverrideEmail($_bcc))) {
            $mail->addBCC($_bcc->getEmail(), $_bcc->getDisplayName());
        }
    }
}


// Set the FROM field
try {
    if ($argv['from']) {
        $_from = User::new()->load($argv['from']);
        $mail->setFrom($_from->getEmail(), $_from->getDisplayName() . Core::getNonProductionEnvironmentMarker());

    } else {
        $mail->setFrom(Email::getDefaultFromAddress(), Email::getDefaultFromName() . Core::getNonProductionEnvironmentMarker());
    }

} catch (ConfigPathDoesNotExistsException $e) {
    // Phoundation  is not properly configured
    Log::error(ts('Cannot send email because the configuration paths "email.from.email" and or "email.from.name" are not correctly configured'), 10);
}


// Add the attachments
foreach ($argv['attachments'] as $_attachment) {
    $mail->addAttachment($_attachment->getSource());
}


// Send the email
if (!$mail->send()) {
    throw new NotificationsException(tr('Cannot send email because ":e"', [':e' => $mail->ErrorInfo]));
}


// Done!
Log::success(ts('Sent email ":subject" to ":user"', [
    ':subject' => $argv['subject'],
    ':user'    => $mail->getToAddresses(),
]), 10);
