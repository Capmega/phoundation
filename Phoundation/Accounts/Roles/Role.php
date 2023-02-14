<?php

namespace Phoundation\Accounts\Roles;

use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Users\Users;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Web\Http\Html\Components\Form;


/**
 * Class Role
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Role extends DataEntry
{
    use DataEntryNameDescription;



    /**
     * Role class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name  = 'role';
        $this->table         = 'accounts_roles';
        $this->unique_column = 'seo_name';

        parent::__construct($identifier);
    }



    /**
     * Add the specified rights to this role
     *
     * @return Rights
     */
    public function rights(): Rights
    {
        if (!$this->list) {
            $this->list = new Rights($this);
        }

        return $this->list;
    }



    /**
     * Returns the users that are linked to this role
     *
     * @return Users
     */
    public function users(): Users
    {
        return new Users($this);
    }



    /**
     * Creates and returns an HTML for the fir
     *
     * @return Form
     */
    public function getRightsHtmlForm(): Form
    {
        $form   = Form::new();
        $rights = $this->rights();
        $select = $rights->getHtmlSelect()->setCache(true);

        foreach ($rights as $right) {
            $select->setSelected($right->getSeoName());
            $form->addContent($select->render() . '<br>');
        }

        // Add extra entry with nothing selected
        $select->clearSelected();
        $form->addContent($select->render());
        return $form;
    }



    /**
     * Sets the available data keys for the Role class
     *
     * @return void
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'id' => [
                'disabled' => true,
                'type'     => 'numeric',
                'label'    => tr('Database ID')
            ],
            'created_on' => [
                'disabled' => true,
                'type'     => 'date',
                'label'    => tr('Created on')
            ],
            'created_by' => [
                'element'  => 'input',
                'disabled' => true,
                'source'   => 'SELECT IFNULL(`username`, `email`) AS `username` FROM `accounts_users` WHERE `id` = :id',
                'execute'  => 'id',
                'label'    => tr('Created by')
            ],
            'meta_id' => [
                'disabled' => true,
                'element'  => null, //Meta::new()->getHtmlTable(), // TODO implement
                'label'    => tr('Meta information')
            ],
            'status' => [
                'disabled' => true,
                'default'  => tr('Ok'),
                'label'    => tr('Status')
            ],
            'meta_state' => [
                'visible' => false,
            ],
            'name' => [
                'label'    => tr('Username')
            ],
            'seo_name' => [
                'display' => false
            ],
            'description' => [
                'element' => 'text',
                'label'   => tr('Description'),
            ]
        ];

        $this->keys_display = [
            'id'          => 12,
            'created_by'  => 6,
            'created_on'  => 6,
            'meta_id'     => 6,
            'status'      => 6,
            'name'        => 12,
            'description' => 12
        ];
    }
}