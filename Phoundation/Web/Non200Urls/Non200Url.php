<?php

namespace Phoundation\Web\Non200Urls;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryComments;
use Phoundation\Data\DataEntry\Traits\DataEntryCookies;
use Phoundation\Data\DataEntry\Traits\DataEntryHeaders;
use Phoundation\Data\DataEntry\Traits\DataEntryHttpCode;
use Phoundation\Data\DataEntry\Traits\DataEntryIpAddress;
use Phoundation\Data\DataEntry\Traits\DataEntryMethod;
use Phoundation\Data\DataEntry\Traits\DataEntryPost;
use Phoundation\Data\DataEntry\Traits\DataEntryReason;
use Phoundation\Data\DataEntry\Traits\DataEntryUrl;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Html\Enums\InputType;
use Phoundation\Web\Html\Enums\InputTypeExtended;
use Phoundation\Web\Routing\Route;


/**
 * Class Non200Url
 *
 * This class represents a single non HTTP-200 URL
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Non200Url extends DataEntry
{
    use DataEntryComments;
    use DataEntryIpAddress;
    use DataEntryHttpCode;
    use DataEntryMethod;
    use DataEntryReason;
    use DataEntryUrl;
    use DataEntryHeaders;
    use DataEntryCookies;
    use DataEntryPost;


    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'web_non200_urls';
    }


    /**
     * @inheritDoc
     */
    public static function getDataEntryName(): string
    {
        return tr('Non HTTP-200 request URL');
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Automatically generates all the internal data for this error URL
     *
     * @param int $http_code
     * @param string|null $reason
     * @return $this
     */
    public function generate(int $http_code, ?string $reason = null): static
    {
        return $this->setUrl(Route::getRequest())
                    ->setIpAddress(Route::getRemoteIp())
                    ->setMethod(Route::getMethod())
                    ->setHttpCode($http_code)
                    ->setReason($reason)
                    ->setHeaders(Route::getHeaders())
                    ->setCookies(Route::getCookies())
                    ->setPost(Route::getPostData());
    }


    /**
     * Process this non HTTP-200 URL and see if it wasn't naughty
     *
     * @return $this
     */
    public function process(): static
    {
throw new UnderConstructionException();
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(Definition::new($this, 'ip_address')
                ->setVisible(false))
            ->addDefinition(Definition::new($this, 'net_len')
                ->setVisible(false))
            ->addDefinition(DefinitionFactory::getIpAddress($this, 'ip_address_human')
                ->setReadonly(true))
            ->addDefinition(Definition::new($this, 'method')
                ->setReadonly(true)
                ->setInputType(InputTypeExtended::variable)
                ->setSize(4)
                ->setMaxlength(12)
                ->setHelpText(tr('The HTTP method used for this request'))
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->sanitizeLowercase()->isInArray(['get', 'head', 'post', 'put', 'delete', 'connect', 'options', 'trace', 'patch']);
                }))
            ->addDefinition(Definition::new($this, 'http_code')
                ->setReadonly(true)
                ->setInputType(InputType::number)
                ->setSize(4)
                ->setMin(100)
                ->setMax(599)
                ->setHelpText(tr('The HTTP method used for this request')))
            ->addDefinition(DefinitionFactory::getComments($this, 'reason')
                ->setOptional(true)
                ->setReadonly(true)
                ->setSize(12)
                ->setMaxlength(255)
                ->setHelpText(tr('Reason why this request failed')))
            ->addDefinition(DefinitionFactory::getUrl($this)
                ->setReadonly(true)
                ->setSize(12)
                ->setHelpText(tr('Request URL that failed')))
            ->addDefinition(DefinitionFactory::getData($this, 'headers')
                ->setOptional(true)
                ->setReadonly(true)
                ->setLabel(tr('Request headers'))
                ->setHelpText(tr('The HTTP headers that were received from the client for this request')))
            ->addDefinition(DefinitionFactory::getData($this, 'cookies')
                ->setOptional(true)
                ->setReadonly(true)
                ->setLabel(tr('Request cookies'))
                ->setHelpText(tr('The cookies that were received from the client for this request')))
            ->addDefinition(DefinitionFactory::getData($this, 'post')
                ->setOptional(true)
                ->setReadonly(true)
                ->setLabel(tr('Request POST data'))
                ->setHelpText(tr('The POST data that was  received from the client for this request')))
            ->addDefinition(DefinitionFactory::getComments($this)
                ->setOptional(true)
                ->setHelpText(tr('Comments on this failed request')));
    }
}