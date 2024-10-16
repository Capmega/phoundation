<?php

/**
 * Class Email
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Emails
 */


declare(strict_types=1);

namespace Phoundation\Emails;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
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
    public static function getDataEntryName(): string
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
     * Sets and returns the field definitions for the data fields in this DataEntry object
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::newUsersEmail($this)
                                           ->setRender(false))

                    ->add(DefinitionFactory::newUsersId($this))

                    ->add(Definition::new($this, 'parents_id')
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::dbid)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        // Ensure the specified parents_id exists
                                        $validator->isOptional()
                                                  ->isQueryResult('SELECT `id` FROM `emails` WHERE `id` = :id', [
                                                      ':id' => '$parents_id',
                                                  ]);
                                    }))

                    ->add(Definition::new($this, 'main')
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::checkbox))

                    ->add(Definition::new($this, 'read')
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::checkbox))

                    ->add(Definition::new($this, 'categories_id')
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::dbid)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        // Ensure the specified parents_id exists
                                        $validator->isOptional()
                                                  ->isQueryResult('SELECT `id` FROM `categories` WHERE `id` = :id', [
                                                      ':id' => '$categories',
                                                  ]);
                                    }))

                    ->add(Definition::new($this, 'templates_id')
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::dbid)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        // Ensure the specified parents_id exists
                                        $validator->isOptional()
                                                  ->isQueryResult('SELECT `id` FROM `storage_pages` WHERE `id` = :id AND `template` = 1', [
                                                      ':id' => '$templates_id',
                                                  ]);
                                    }))

                    ->add(DefinitionFactory::newCode($this)
                                           ->setSize(3))

                    ->add(Definition::new($this, 'subject')
                                    ->setSize(3)
                                    ->setMaxlength(255))

                    ->add(Definition::new($this, 'body')
                                    ->setSize(3)
                                    ->setSize(12)
                                    ->setMaxlength(16_777_215));
    }
}
