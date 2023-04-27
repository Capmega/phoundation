<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryIpAddress;
use Phoundation\Data\DataEntry\Traits\DataEntryTimezone;
use Phoundation\Data\DataEntry\Traits\DataEntryUserAgent;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Traits\DataGeoIp;
use Phoundation\Data\Validator\Interfaces\DataValidator;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\GeoIp\GeoIp;
use Phoundation\Geo\Timezones\Timezones;

/**
 * SignIn class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class SignIn extends DataEntry
{
    use DataEntryUserAgent;
    use DataEntryIpAddress;
    use DataEntryTimezone;
    use DataGeoIp;

    /**
     * SignIn class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name  = 'signin';

        parent::__construct($identifier);
    }


    /**
     * Detects signin information automatically
     *
     * @return $this
     */
    public static function detect(): static
    {
        return SignIn::new()
            ->setIpAddress($_SERVER['REMOTE_ADDR'])
            ->setUserAgent($_SERVER['HTTP_USER_AGENT'])
            ->setGeoIp(GeoIp::detect($_SERVER['REMOTE_ADDR']));
    }


    /**
     * Validates the data contained in the validator object
     *
     * @param DataValidator $validator
     * @param bool $no_arguments_left
     * @param bool $modify
     * @return array
     */
    protected function validate(DataValidator $validator, bool $no_arguments_left, bool $modify): array
    {
        $data = $validator
            ->select($this->getAlternateValidationField('ip_address'), true)->hasMaxCharacters(16)
            ->select($this->getAlternateValidationField('net_len'), true)->isOptional()->isNatural()->isLessThan(48)
            ->select($this->getAlternateValidationField('ip_address_human'), true)->isIp()
            ->select($this->getAlternateValidationField('user_agent'), true)->isOptional()->hasMaxCharacters(2048)
            ->select($this->getAlternateValidationField('latitude'), true)->isOptional()->isLessThan(90)->isMoreThan(-90)
            ->select($this->getAlternateValidationField('longitude'), true)->isOptional()->isLessThan(180)->isMoreThan(-180)
            ->select($this->getAlternateValidationField('country'), true)->or('countries_id')->isName()->isQueryColumn ('SELECT `name` FROM `geo_countries` WHERE `name` = :name AND `status` IS NULL', [':name' => '$country'])
            ->select($this->getAlternateValidationField('countries_id'), true)->or('country')->isId()->isQueryColumn   ('SELECT `id`   FROM `geo_countries` WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$countries_id'])
            ->select($this->getAlternateValidationField('timezone'), true)->or('timezones_id')->isName()->isQueryColumn('SELECT `name` FROM `geo_timezones` WHERE `name` = :name AND `status` IS NULL', [':name' => '$timezone'])
            ->select($this->getAlternateValidationField('timezones_id'), true)->or('timezone')->isId()->isQueryColumn  ('SELECT `id`   FROM `geo_timezones` WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$timezones_id'])
            ->noArgumentsLeft($no_arguments_left)
            ->validate();

        return $data;
    }


    /**
     * Sets the available data keys for the User class
     *
     * @return DataEntryFieldDefinitionsInterface
     */
    protected static function getFieldDefinitions(): array
    {
       return [
           'ip_address' => [
               'visible'  => false
           ],
           'net_len' => [
               'visible'  => false
           ],
           'ip_address_human' => [
               'readonly'  => true,
               'size'      => 6,
               'maxlength' => 48,
               'label'     => tr('IP Address')
           ],
            'user_agent' => [
                'readonly'  => true,
                'size'      => 6,
                'maxlength' => 2040,
                'label'     => tr('User agent')
            ],
            'latitude' => [
                'readonly'  => true,
                'size'      => 6,
                'type'      => 'numeric',
                'min'       => -90,
                'max'       => 90,
                'step'      => 'any',
                'maxlength' => 16,
                'label'     => tr('Latitude'),
            ],
            'longitude' => [
                'readonly'  => true,
                'size'      => 6,
                'type'      => 'numeric',
                'min'       => -180,
                'max'       => 180,
                'maxlength' => 16,
                'step'      => 'any',
                'label'     => tr('Longitude')
            ],
           'countries_id' => [
               'element'  => function (string $key, array $data, array $source) {
                   return Countries::getHtmlCountriesSelect($key)
                       ->setDisabled(true)
                       ->setSelected(isset_get($source['countries_id']))
                       ->render();
               },
               'label' => tr('Country'),
               'size'  => 6,
           ],
           'timezones_id' => [
               'element'  => function (string $key, array $data, array $source) {
                   return Timezones::getHtmlSelect($key)
                       ->setDisabled(true)
                       ->setSelected(isset_get($source['timezones_id']))
                       ->render();
               },
               'label' => tr('Timezone'),
               'size'  => 6,
           ],
        ];
    }
}