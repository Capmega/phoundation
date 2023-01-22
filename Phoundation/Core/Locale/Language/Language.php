<?php

namespace Phoundation\Core\Locale\Language;

use Phoundation\Data\DataEntry;
use Phoundation\Data\DataEntryNameDescription;


/**
 * Language class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Language extends DataEntry
{
    use DataEntryNameDescription;



    /**
     * Language class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        self::$entry_name    = 'language';
        $this->table         = 'languages';
        $this->unique_column = 'seo_name';

        parent::__construct($identifier);
    }



    /**
     * Returns the code_639_1 for this language
     *
     * @return string|null
     */
    public function getCode_639_1(): ?string
    {
        return $this->getDataValue('code_639_1');
    }



    /**
     * Sets the code_639_1 for this language
     *
     * @param string|null $code_639_2_b
     * @return static
     */
    public function setCode_639_1(?string $code_639_1): static
    {
        return $this->setDataValue('code_639_1', $code_639_1);
    }



    /**
     * Returns the code_639_2_b for this language
     *
     * @return string|null
     */
    public function getCode_639_2_b(): ?string
    {
        return $this->getDataValue('code_639_2_b');
    }



    /**
     * Sets the code_639_2_b for this language
     *
     * @param string|null $code_639_2_b
     * @return static
     */
    public function setCode_639_2_b(?string $code_639_2_b): static
    {
        return $this->setDataValue('code_639_2_b', $code_639_2_b);
    }



    /**
     * Returns the code_639_2_t for this language
     *
     * @return string|null
     */
    public function getCode_639_2_t(): ?string
    {
        return $this->getDataValue('code_639_2_t');
    }



    /**
     * Sets the code_639_2_t for this language
     *
     * @param string|null $code_639_2_t
     * @return static
     */
    public function setCode_639_2_t(?string $code_639_2_t): static
    {
        return $this->setDataValue('code_639_2_t', $code_639_2_t);
    }



    /**
     * Returns the code_639_3 for this language
     *
     * @return string|null
     */
    public function getCode_639_3(): ?string
    {
        return $this->getDataValue('code_639_3');
    }



    /**
     * Sets the code_639_3 for this language
     *
     * @param string|null $code_639_3
     * @return static
     */
    public function setCode_639_3(?string $code_639_3): static
    {
        return $this->setDataValue('code_639_3', $code_639_3);
    }



    /**
     * @inheritDoc
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
            'name' => [
                'disabled'  => true,
                'label'     => tr('Name')
            ],
            'seo_name' => [
                'display'  => false,
            ],
            'code_639_1' => [
                'disabled' => true,
                'type'     => 'text',
                'label'    => tr('ISO 639-1 code')
            ],
            'code_639_2_t' => [
                'disabled' => true,
                'type'     => 'text',
                'label'    => tr('ISO 639-2/T code')
            ],
            'code_639_2_b' => [
                'disabled' => true,
                'type'     => 'text',
                'label'    => tr('ISO 639-2/B code')
            ],
            'code_639_3' => [
                'disabled' => true,
                'type'     => 'text',
                'label'    => tr('ISO 639-3 code')
            ],
            'description' => [
                'label'   => tr('Description'),
                'element' => 'text'
            ]
        ];

        $this->form_keys = [
            'id'            => 12,
            'created_by'    => 6,
            'created_on'    => 6,
            'meta_id'       => 6,
            'status'        => 6,
            'name'          => 12,
            'code_639_1'    => 6,
            'code_639_2_t'  => 6,
            'code_639_2_b'  => 6,
            'code_639_3'    => 6,
            'description'   => 12
        ];
    }
}