<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryIpAddress;
use Phoundation\Data\DataEntry\Traits\DataEntryTimezone;
use Phoundation\Data\DataEntry\Traits\DataEntryUserAgent;
use Phoundation\Data\Traits\DataGeoIp;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\GeoIp\Exception\GeoIpException;
use Phoundation\Geo\GeoIp\GeoIp;
use Phoundation\Geo\Timezones\Timezones;
use Phoundation\Web\Html\Enums\InputType;
use Phoundation\Web\Html\Enums\InputTypeExtended;


/**
 * SignIn class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @param bool|null $meta_enabled
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null)
    {
        parent::__construct($identifier, $column, $meta_enabled);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'accounts_signins';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return 'signin';
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
     * Detects sign-in information automatically
     *
     * @return static
     */
    public static function detect(): static
    {
        $signin = static::new()
            ->setIpAddress($_SERVER['REMOTE_ADDR'])
            ->setUserAgent($_SERVER['HTTP_USER_AGENT']);

        try {
            $signin->setGeoIp(GeoIp::detect($_SERVER['REMOTE_ADDR']));

        } catch (GeoIpException $e) {
            Log::error('Failed to detect GeoIP location information with following exception');
            Log::error($e);
        }

        return $signin;
    }


    /**
     * Sets the available data keys for the User class
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(Definition::new($this, 'ip_address')
                ->setVisible(false))
            ->addDefinition(Definition::new($this, 'net_len')
                ->setVisible(false))
            ->addDefinition(Definition::new($this, 'ip_address_human')
                ->setReadonly(true)
                ->setSize(6)
                ->setMaxlength(48)
                ->setLabel(tr('IP Address')))
            ->addDefinition(Definition::new($this, 'user_agent')
                ->setOptional(true)
                ->setReadonly(true)
                ->setSize(6)
                ->setMaxlength(2040)
                ->setLabel(tr('User agent')))
            ->addDefinition(Definition::new($this, 'latitude')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::number)
                ->setSize(6)
                ->setMin(-90)
                ->setMax(90)
                ->setStep('any')
                ->setLabel(tr('Latitude')))
            ->addDefinition(Definition::new($this, 'longitude')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::number)
                ->setSize(6)
                ->setMin(-180)
                ->setMax(180)
                ->setStep('any')
                ->setLabel(tr('Longitude')))
            ->addDefinition(Definition::new($this, 'countries_id')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputTypeExtended::dbid)
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    return Countries::getHtmlCountriesSelect()
                        ->setDisabled(true)
                        ->setName($field_name)
                        ->setSelected(isset_get($source['countries_id']))
                        ->render();
                })
                ->setSize(6)
                ->setLabel(tr('Country'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isQueryResult('SELECT `name` FROM `geo_countries` WHERE `id` = :id AND `status` IS NULL', [':id' => '$countries_id']);
                }))
            ->addDefinition(Definition::new($this, 'timezones_id')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputTypeExtended::dbid)
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    return Timezones::new()->getHtmlSelect()
                        ->setDisabled(true)
                        ->setName($field_name)
                        ->setSelected(isset_get($source['timezones_id']))
                        ->render();
                })
                ->setSize(6)
                ->setLabel(tr('Timezone'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isDbId()->isQueryResult('SELECT `id` FROM `geo_timezones` WHERE `id` = :id AND `status` IS NULL', [':id' => '$timezones_id']);
                }));
    }
}
