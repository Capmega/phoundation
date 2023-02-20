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
        $this->unique_column = 'id';

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
     * Sets the available data keys for the SignIn class
     *
     * @return void
     */
    protected function setKeys(): void
    {
       $this->keys = [
           'ip_address' => [
               'disabled' => true,
               'label'    => tr('IP Address')
           ],
           'ip_address_human' => [
               'visible' => false,
           ],
            'net_len' => [
                'display'  => false
            ],
            'user_agent' => [
                'disabled' => true,
                'label' => tr('User agent')
            ],
            'latitude' => [
                'disabled' => true,
                'label'    => tr('Latitude'),
            ],
            'longitude' => [
                'disabled' => true,
                'label'    => tr('Longitude')
            ],
           'countries_id' => [
               'element'  => function (string $key, array $data, array $source) {
                   return Countries::getHtmlCountriesSelect($key)
                       ->setDisabled(true)
                       ->setSelected(isset_get($source['countries_id']))
                       ->render();
               },
               'label'    => tr('Country')
           ],
           'timezones_id' => [
               'element'  => function (string $key, array $data, array $source) {
                   return Timezones::getHtmlSelect($key)
                       ->setDisabled(true)
                       ->setSelected(isset_get($source['timezones_id']))
                       ->render();
               },
               'label'    => tr('Timezone')
           ],
        ];

        $this->keys_display = [
            'ip_address'   => 6,
            'user_agent'   => 6,
            'latitude'     => 4,
            'longitude'    => 4,
            'countries_id' => 4,
            'timezones_id' => 4,
        ] ;

        parent::setKeys();
    }
}