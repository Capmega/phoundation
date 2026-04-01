<?php

/**
 * Class Email
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Emails
 */


declare(strict_types=1);

namespace Phoundation\Emails;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Os\Processes\Commands\Pho;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Html\Pages\Template;
use PHPMailer\PHPMailer\PHPMailer;


class Email extends DataEntry
{
    protected ?EmailAddress $from = null;


    /**
     * The template for this email
     *
     * @var Template $template
     */
    protected Template $template;


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'emails';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('Email');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Returns a configured email address that (if configured) will always be used for all emails
     *
     * @param UserInterface|null $_user
     *
     * @return string|null
     */
    public static function getOverrideEmail(?UserInterface $_user = null): ?string
    {
        $_user = $_user ?? Session::getUserObject();

        if (!$_user->isNew() and $_user->hasAllRights('force-email')) {
            // The user has the "force-email" right, which cancels email overrides
            return null;
        }

        return get_null(config()->getString('email.override.all', ''));
    }


    /**
     * Sends the email
     *
     * @param bool $background
     *
     * @return static
     */
    public function send(bool $background = true): static
    {
        if ($background) {
            // Set status to SEND so that it will send as a background process
            $this->setStatus('PENDING-SEND');
            Pho::new()
               ->setPhoCommands('emails pending send')
               ->execute(EnumExecuteMethod::background);

        } else {
            $from      = $this->from;
            $phpmailer = new PHPMailer();
            $phpmailer->isSMTP();

            // Setup email host configuration
            $phpmailer->Host       = $from->getSmtpHost();
            $phpmailer->SMTPAuth   = $from->getSmtpAuth();
            $phpmailer->SMTPSecure = $from->getSmtpSecure();
            $phpmailer->Port       = $from->getPort();
            $phpmailer->Username   = $from->getUser();
            $phpmailer->Password   = $from->getPass();

            // Build email
            $phpmailer->body    = $this->body;
            $phpmailer->subject = $this->subject;
            $phpmailer->isHTML($this->is_html);
            $phpmailer->setFrom($this->from->getEmail(), $this->from->getName());
            $phpmailer->setReplyTo($this->reply_to->getEmail(), $this->reply_to->getName());

            foreach ($this->cc as $cc) {
                $phpmailer->setCc($cc->getEmail(), $cc->getName());
            }

            foreach ($this->bcc as $bcc) {
                $phpmailer->setBcc($bcc->getEmail(), $bcc->getName());
            }

            // Send the email
            $phpmailer->send();
            $this->setStatus('SENT');
        }

        return $this;
    }


    /**
     * Returns the configured default hostname for sending emails
     *
     * @return string|null
     */
    public static function getDefaultHostname(): ?string
    {
        return get_null(config()->getString('email.defaults.hostname', ''));
    }


    /**
     * Returns the configured default port for sending emails
     *
     * @return int
     */
    public static function getDefaultPort(): int
    {
        return get_null(config()->getInteger('email.defaults.port', 25));
    }


    /**
     * Returns the configured default email address for developer emails
     *
     * @return string|null
     */
    public static function getDefaultDeveloperAddress(): ?string
    {
        return get_null(config()->getString('email.defaults.developer', ''));
    }


    /**
     * Returns the configured default from email address for developer emails
     *
     * @return string|null
     */
    public static function getDefaultFromAddress(): ?string
    {
        return get_null(config()->getString('email.defaults.from.email', ''));
    }


    /**
     * Returns the configured default from email name for developer emails
     *
     * @return string|null
     */
    public static function getDefaultFromName(): ?string
    {
        return get_null(config()->getString('email.defaults.from.name', ''));
    }


    /**
     * Returns the configured default connect timeout for emails
     *
     * @return int|null
     */
    public static function getDefaultTimeout(): ?int
    {
        return get_null(config()->getInteger('email.defaults.timeout', 5));
    }


    /**
     * Sets and returns the field definitions for the data fields in this DataEntry object
     *
     * @param DefinitionsInterface $_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $_definitions): static
    {
        $_definitions->add(DefinitionFactory::newUsersEmail()
                                             ->setRender(false))

                      ->add(DefinitionFactory::newUsersId())

                      ->add(Definition::new('parents_id')
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::dbid)
                                    ->addValidationFunction(function (ValidatorInterface $_validator) {
                                        // Ensure the specified parents_id exists
                                        $_validator->isOptional()
                                                  ->isQueryResult('SELECT `id` 
                                                                   FROM   `emails`
                                                                   WHERE  `id` = :id
                                                                   AND   (`status` IS NULL OR `status` NOT LIKE "deleted%")', [
                                                                       ':id' => '$parents_id',
                                                  ]);
                                    }))

                    ->add(Definition::new('main')
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::checkbox))

                    ->add(Definition::new('read')
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::checkbox))

                    ->add(Definition::new('categories_id')
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::dbid)
                                    ->addValidationFunction(function (ValidatorInterface $_validator) {
                                        // Ensure the specified parents_id exists
                                        $_validator->isOptional()
                                                  ->isQueryResult('SELECT `id`
                                                                   FROM   `categories` 
                                                                   WHERE  `id` = :id
                                                                   AND   (`status` IS NULL OR `status` NOT LIKE "deleted%")', [
                                                                       ':id' => '$categories',
                                                  ]);
                                    }))

                    ->add(Definition::new('templates_id')
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::dbid)
                                    ->addValidationFunction(function (ValidatorInterface $_validator) {
                                        // Ensure the specified parents_id exists
                                        $_validator->isOptional()
                                                  ->isQueryResult('SELECT `id` 
                                                                   FROM   `storage_pages` 
                                                                   WHERE  `id` = :id 
                                                                   AND    `template` = 1
                                                                   AND   (`status` IS NULL OR `status` NOT LIKE "deleted%")', [
                                                                       ':id' => '$templates_id',
                                                  ]);
                                    }))

                    ->add(DefinitionFactory::newCode()
                                           ->setSize(3))

                    ->add(Definition::new('subject')
                                    ->setSize(3)
                                    ->setMaxLength(255))

                    ->add(Definition::new('body')
                                    ->setSize(3)
                                    ->setSize(12)
                                    ->setMaxLength(16_777_215));

        return $this;
    }
}
