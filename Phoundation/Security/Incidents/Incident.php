<?php

namespace Phoundation\Security\Incidents;

use Phoundation\Core\Log;
use Phoundation\Data\DataEntry;
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
 */
class Incident extends DataEntry
{
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
        self::$entry_name    = 'incident';
        $this->table         = 'security_incidents';
        $this->unique_column = 'id';

        parent::__construct($identifier);
    }



    /**
     * Returns the type for this object
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getDataValue('type');
    }



    /**
     * Sets the type for this object
     *
     * @param string|null $type
     * @return static
     */
    public function setType(?string $type): static
    {
        return $this->setDataValue('type', $type);
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
     * @param Severity $severity
     * @return static
     */
    public function setSeverity(Severity $severity): static
    {
        return $this->setDataValue('severity', $severity->value);
    }



    /**
     * Returns the title for this object
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->getDataValue('title');
    }



    /**
     * Sets the title for this object
     *
     * @param string|null $title
     * @return static
     */
    public function setTitle(?string $title): static
    {
        return $this->setDataValue('title', $title);
    }



    /**
     * Returns the details for this object
     *
     * @return string|null
     */
    public function getDetails(): ?string
    {
        return Json::decode($this->getDataValue('details'));
    }



    /**
     * Sets the details for this object
     *
     * @param array|null $details
     * @return static
     */
    public function setDetails(?array $details): static
    {
        return $this->setDataValue('details', Json::encode($details));
    }



    /**
     * Saves the incident to database
     *
     * @return $this
     */
    public function save(): static
    {
        if ($this->log) {
            Log::warning(tr('Security incident (:severity): :message', [
                ':severity' => strtoupper($this->getSeverity()),
                ':message'  => $this->getTitle()
            ]));
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
            'id' => [
                'display'  => true,
                'disabled' => true,
                'type'     => 'numeric',
                'label'    => tr('Database ID')
            ],
            'created_by' => [
                'element'  => 'input',
                'display'  => true,
                'disabled' => true,
                'source'   => 'SELECT IFNULL(`username`, `email`) AS `username` FROM `accounts_users` WHERE `id` = :id',
                'execute'  => 'id',
                'label'    => tr('Created by')
            ],
            'created_on' => [
                'display'  => true,
                'disabled' => true,
                'type'     => 'date',
                'label'    => tr('Created on')
            ],
            'meta_id' => [
                'display'  => true,
                'disabled' => true,
                'element'  => null, //Meta::new()->getHtmlTable(), // TODO implement
                'label'    => tr('Meta information')
            ],
            'status' => [
                'disabled' => true,
                'default'  => tr('Ok'),
                'label'    => tr('Status')
            ],
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
                'default'   => tr('Unknown'),
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

        $this->form_keys = [
            'id'         => 12,
            'created_by' => 6,
            'created_on' => 6,
            'meta_id'    => 6,
            'status'     => 6,
            'type'       => 6,
            'severity'   => 6,
            'title'      => 12,
            'details'    => 12,
        ] ;
    }
}