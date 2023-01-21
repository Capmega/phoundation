<?php

namespace Phoundation\Security;

use Phoundation\Data\DataEntry;



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
     * Returns the severity for this object
     *
     * @return string|null
     */
    public function getSeverity(): ?string
    {
        return $this->getDataValue('severity');
    }



    /**
     * Sets the severity for this object
     *
     * @param string|null $severity
     * @return static
     */
    public function setSeverity(?string $severity): static
    {
        return $this->setDataValue('severity', $severity);
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
     * Returns the description for this object
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getDataValue('description');
    }



    /**
     * Sets the description for this object
     *
     * @param string|null $description
     * @return static
     */
    public function setDescription(?string $description): static
    {
        return $this->setDataValue('description', $description);
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
                'type'      => 'date',
                'null_type' => 'text',
                'default'   => '-',
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
            'description' => [
                'element'  => 'text',
                'label'    => tr('Description'),
            ]
        ];

        $this->form_keys = [
            'id'          => 12,
            'created_by'  => 6,
            'created_on'  => 6,
            'meta_id'     => 6,
            'status'      => 6,
            'type'        => 6,
            'severity'    => 6,
            'title'       => 12,
            'description' => 12,
        ] ;
    }
}