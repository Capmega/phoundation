<?php

namespace Phoundation\Accounts\Users;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryIpAddress;
use Phoundation\Data\DataEntry\Traits\DataEntryTimezone;
use Phoundation\Data\DataEntry\Traits\DataEntryUserAgent;
use Phoundation\Data\Traits\DataGeoIp;
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
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name  = 'signin';
        $this->table         = 'accounts_signins';
        $this->unique_field = 'id';

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
     * Sets the available data keys for the User class
     *
     * @return array
     */
    public static function getFieldDefinitions(): array
    {
       return [
           'ip_address' => [
               'display'  => false
           ],
           'net_len' => [
               'display'  => false
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