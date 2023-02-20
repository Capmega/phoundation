<?php

namespace Phoundation\Security\Incidents;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryDetails;
use Phoundation\Data\DataEntry\Traits\DataEntryTitle;
use Phoundation\Data\DataEntry\Traits\DataEntryType;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Utils\Json;


/**
 * Class Incident
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 * @todo Incidents should be able to throw exceptions depending on type. AuthenticationFailureExceptions, for example, should be thrown from here so that it is no longer required for the developer to both register the incident AND throw the exception
 */
class Incident extends DataEntry
{
    use DataEntryType;
    use DataEntryTitle;
    use DataEntryDetails;



    /**
     * Sets if this incident will be logged in the text log
     *
     * @var bool
     */
    protected bool $log = true;



    /**
     * Incident class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name  = 'incident';
        $this->table         = 'security_incidents';
        $this->unique_column = 'id';

        parent::__construct($identifier);
    }



    /**
     * Returns if this incident will be logged in the text log
     *
     * @return bool
     */
    public function getLog(): bool
    {
        return $this->log;
    }



    /**
     * Sets if this incident will be logged in the text log
     *
     * @param bool $log
     * @return static
     */
    public function setLog(bool $log): static
    {
        $this->log = $log;
        return $this;
    }



    /**
     * Returns the severity for this object
     *
     * @return string
     */
    public function getSeverity(): string
    {
        return $this->getDataValue('severity');
    }



    /**
     * Sets the severity for this object
     *
     * @param Severity|string $severity
     * @return static
     */
    public function setSeverity(Severity|string $severity): static
    {
        if (is_string($severity)) {
            $severity = Severity::from($severity);
        }

        return $this->setDataValue('severity', $severity->value);
    }



    /**
     * Saves the incident to database
     *
     * @return $this
     */
    public function save(?string $comments = null): static
    {
        if ($this->log) {
            $severity = strtolower($this->getSeverity());

            switch ($severity){
                case 'notice':
                    Log::warning(tr('Security notice: :message', [
                        ':message'  => $this->getTitle()
                    ]));

                    break;

                case 'high':
                    // no break
                case 'severe':
                    Log::error(tr('Security incident (:severity): :message', [
                        ':severity' => $severity,
                        ':message'  => $this->getTitle()
                    ]));

                    break;

                default:
                    Log::warning(tr('Security incident (:severity): :message', [
                        ':severity' => $severity,
                        ':message'  => $this->getTitle()
                    ]));
            }
        }

        return parent::save();
    }



    /**
     * Set the form keys for this object
     *
     * @return void
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'severity' => [
                'disabled'  => true,
                'type'      => 'text',
                'required'  => true,
                'source'    => [
                    Severity::notice->value => tr('Notice'),
                    Severity::low->value    => tr('Low'),
                    Severity::medium->value => tr('Medium'),
                    Severity::high->value   => tr('High'),
                    Severity::severe->value => tr('Severe')
                ],
                'label'     => tr('Severity')
            ],
            'type' => [
                'disabled'  => true,
                'null_type' => 'text',
                'display_default' => tr('Unknown'),
                'label'     => tr('Incident type')
            ],
            'title' => [
                'disabled' => true,
                'db_null'  => false,
                'type'     => 'numeric',
                'label'    => tr('Title')
            ],
            'details' => [
                'element'  => 'text',
                'label'    => tr('Details'),
            ]
        ];

        $this->keys_display = [
            'type'       => 6,
            'severity'   => 6,
            'title'      => 12,
            'details'    => 12,
        ] ;

        parent::setKeys();
    }



    /**
     * Throw an incidents exception
     *
     * @return void
     */
    #[NoReturn] public function throw(): void
    {
        throw IncidentsException::new($this->getTitle(), $this->getDetails());
    }
}