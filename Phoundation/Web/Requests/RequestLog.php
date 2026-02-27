<?php

/**
 * Class RequestLog
 *
 * This class manages multiple entries from the table web_requests
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests;

use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryAction;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryComments;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCookies;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringDomain;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryHeaders;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryHttpCode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryIntegerIncidentsId;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryIntegerPid;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryMethod;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPost;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringGlobalId;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringLocalId;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringPlatform;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringRemoteIp;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringRemoteIpReal;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUrl;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Web\Html\Enums\EnumElement;


class RequestLog extends DataEntry
{
    use TraitDataEntryMethod;
    use TraitDataEntryUrl;
    use TraitDataEntryStringDomain;
    use TraitDataEntryAction;
    use TraitDataEntryHttpCode;
    use TraitDataEntryStringGlobalId;
    use TraitDataEntryStringLocalId;
    use TraitDataEntryIntegerIncidentsId;
    use TraitDataEntryIntegerPid;
    use TraitDataEntryStringPlatform;
    use TraitDataEntryStringRemoteIp;
    use TraitDataEntryStringRemoteIpReal;
    use TraitDataEntryComments;
    use TraitDataEntryPost;
    use TraitDataEntryCookies;
    use TraitDataEntryHeaders;


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'web_requests_logs';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('Web Request Log');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'local_id';
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $_definitions): static
    {
        $_definitions->removeKeys('meta_divider')

                     ->add(DefinitionFactory::newCreatedBy()
                                            ->setOptional(true)
                                            ->setRender(true))

                     ->add(DefinitionFactory::newDivider('meta_divider'))

                     ->add(Definition::new('platform')
                                     ->setElement(EnumElement::select)
                                     ->setLabel(tr('Platform'))
                                     ->setDisabled(true)
                                     ->setReadonly(true)
                                     ->setSize(6)
                                     ->setSource([
                                         'web' => tr('Web page'),
                                         'cli' => tr('Command Line Interface'),
                                     ]))

                    ->add(Definition::new('method')
                                    ->setElement(EnumElement::select)
                                    ->setLabel(tr('Method'))
                                    ->setDisabled(true)
                                    ->setReadonly(true)
                                    ->setSize(6)
                                    ->setSource([
                                        'GET'     => 'GET',
                                        'POST'    => 'POST',
                                        'PUT'     => 'PUT',
                                        'DELETE'  => 'DELETE',
                                        'PATCH'   => 'PATCH',
                                        'HEAD'    => 'HEAD',
                                        'OPTIONS' => 'OPTIONS',
                                        'CONNECT' => 'CONNECT',
                                        'TRACE'   => 'TRACE',
                                    ]))

                    ->add(DefinitionFactory::newDomain()
                                           ->setLabel(tr('Domain'))
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setSize(6)
                                           ->setMaxLength(255))

                    ->add(DefinitionFactory::newIpAddress('remote_ip')
                                           ->setLabel(tr('Remote IP Address'))
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setSize(3))

                    ->add(DefinitionFactory::newIpAddress('remote_ip_real')
                                           ->setLabel(tr('Real Remote IP Address'))
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setSize(3))

                    ->add(DefinitionFactory::newCode('global_id')
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setMaxLength(8)
                                           ->setHelpText(tr('Global ID'))
                                           ->setSize(3))

                    ->add(DefinitionFactory::newCode('local_id')
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setMaxLength(8)
                                           ->setHelpText(tr('Local ID'))
                                           ->setSize(3))

                    ->add(DefinitionFactory::newDatabaseId('pid')
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setHelpText(tr('Process ID'))
                                           ->setSize(3))

                    ->add(DefinitionFactory::newDatabaseId('incidents_id')
                                           ->setRender(false)
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setHelpText(tr('Incidents ID'))
                                           ->setSize(3))

                    ->add(DefinitionFactory::newDatabaseId('http_code')
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setHelpText(tr('Process ID'))
                                           ->setMin(0)
                                           ->setMax(1000)
                                           ->setSize(3))

                    ->add(DefinitionFactory::newCode('action')
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setHelpText(tr('Action taken'))
                                           ->setMaxLength(16)
                                           ->setSize(3))

                    ->add(DefinitionFactory::newCode('action')
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setHelpText(tr('Action taken'))
                                           ->setMaxLength(16)
                                           ->setSize(3))

                    ->add(Definition::new('url')
                                    ->setSize(2)
                                    ->setOptional(true)
                                    ->setMaxLength(2048)
                                    ->setCliColumn('--url')
                                    ->setLabel(tr('URL')))

                    ->add(Definition::new('url')
                                    ->setSize(2)
                                    ->setOptional(true)
                                    ->setMaxLength(2048)
                                    ->setCliColumn('--url')
                                    ->setLabel(tr('URL')))

                    ->add(Definition::new('headers')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setElement(EnumElement::textarea)
                                    ->setLabel(tr('Headers'))
                                    ->setMaxlength(16_777_215)
                                    ->setRows(10)
                                    ->setSize(12))

                    ->add(Definition::new('cookies')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setElement(EnumElement::textarea)
                                    ->setLabel(tr('Cookies'))
                                    ->setMaxlength(16_777_215)
                                    ->setRows(10)
                                    ->setSize(12))

                    ->add(Definition::new('post')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setElement(EnumElement::textarea)
                                    ->setLabel(tr('POST data'))
                                    ->setMaxlength(16_777_215)
                                    ->setRows(10)
                                    ->setSize(12));

        return $this;
    }
}
