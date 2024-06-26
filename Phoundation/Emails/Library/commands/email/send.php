<?php

declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Accounts\Users\Users;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Notifications\Exception\NotificationsException;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\ConfigPathDoesNotExistsException;
use PHPMailer\PHPMailer\PHPMailer;


/**
 * Command email/send
 *
 * This script can send out emails
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-f,--from' => [
                                              'word'   => function ($word) { return Users::new()->load()->keepMatchingKeys($word)->limitAutoComplete(); },
                                              'noword' => function () { return Users::new()->load()->limitAutoComplete(); },
                                          ],
                                          '-t,--to'   => [
                                              'word'   => function ($word) { return Users::new()->load()->keepMatchingKeys($word)->limitAutoComplete(); },
                                              'noword' => function () { return Users::new()->load()->limitAutoComplete(); },
                                          ],
                                          '-s,--to'   => true,
                                          '-b,--body' => true,
                                          '-h,--html' => false,
                                      ],
                                  ]);

CliDocumentation::setUsage('./pho email send');

CliDocumentation::setHelp('This command can send out emails


ARGUMENTS


-b,--body STRING                        The email body

[-f,--from EMAIL]                       The email address of the user from which this mail should be sent

-h,--html                               If specified, the email body will be sent as HTML

-s,--subject STRING                     The subject line for the email

-t,--to EMAIL                           The email address of the user to which this mail should be sent');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-f,--from', true)->isOptional()->isEmail()
                     ->select('-t,--to', true)->isEmail()
                     ->select('-s,--subject', true)->hasMaxCharacters(255)
                     ->select('-b,--body', true)->hasMaxCharacters(16_777_200)
                     ->select('-h,--html')->isOptional()->isBoolean()
                     ->validate();


// TODO Use the Email library for sending emails, once its ready
//Email::new()
//    ->addFrom($argv['from'])
//    ->addTo($argv['to'])
//    ->setSubject($argv['subject'])
//    ->setBody($argv['body'])
//    ->send();


// Send emails directly using PHPMailer
$to   = User::load($argv['to']);
$mail = new PHPMailer();

$mail->Host    = "10.10.0.9";
$mail->Port    = "25";
$mail->Subject = $argv['subject'];
$mail->Body    = $argv['body'];

$mail->isSMTP();
$mail->isHTML($argv['html']);
$mail->addAddress($to->getEmail(), $to->getDisplayName());

try {
    if ($argv['from']) {
        $from = User::load($argv['from']);
        $mail->setFrom($from->getEmail(), $from->getDisplayName());

    } else {
        $mail->setFrom(Config::getString('email.from.email'), Config::getString('email.from.name', 'Your Phoundation project'));
    }

} catch (ConfigPathDoesNotExistsException $e) {
    // Phoundation isn't properly configured
    Log::error(tr('Cannot send email because the email.from.email and or email.from.name are not correctly configured'));
}

if (!$mail->send()) {
    throw new NotificationsException(tr('Cannot send email because ":e"', [':e' => $mail->ErrorInfo]));
}

Log::success(tr('Sent email ":subject" to ":user"', [
    ':subject' => $argv['subject'],
    ':user'    => $to->getLogId(),
]));
