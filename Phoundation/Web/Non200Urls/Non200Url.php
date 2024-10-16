<?php

/**
 * Class Non200Url
 *
 * This class represents a single non HTTP-200 URL
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Non200Urls;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryComments;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCookies;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryHeaders;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryHttpCode;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryIpAddress;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryMethod;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryPost;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryReason;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUrl;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Routing\Route;


class Non200Url extends DataEntry
{
    use TraitDataEntryComments;
    use TraitDataEntryIpAddress;
    use TraitDataEntryHttpCode;
    use TraitDataEntryMethod;
    use TraitDataEntryReason;
    use TraitDataEntryUrl;
    use TraitDataEntryHeaders;
    use TraitDataEntryCookies;
    use TraitDataEntryPost;

    /**
     * @inheritDoc
     */
    public static function getTable(): ?string
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
     * @param int         $http_code
     * @param string|null $reason
     *
     * @return static
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
     * @return static
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
        $definitions->add(Definition::new($this, 'ip_address_binary')
                                    ->setIgnored(true)
                                    ->setRender(false))

                    ->add(Definition::new($this, 'net_len')
                                    ->setIgnored(true)
                                    ->setRender(false))

                    ->add(DefinitionFactory::newIpAddress($this, 'ip_address')
                                           ->setReadonly(true))

                    ->add(Definition::new($this, 'method')
                                    ->setReadonly(true)
                                    ->setInputType(EnumInputType::variable)
                                    ->setSize(4)
                                    ->setMaxlength(12)
                                    ->setHelpText(tr('The HTTP method used for this request'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->sanitizeLowercase()
                                                  ->isInArray([
                                                      'get',
                                                      'head',
                                                      'post',
                                                      'put',
                                                      'delete',
                                                      'connect',
                                                      'options',
                                                      'trace',
                                                      'patch',
                                                  ]);
                                    }))

                    ->add(Definition::new($this, 'http_code')
                                    ->setReadonly(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setSize(4)
                                    ->setMin(100)
                                    ->setMax(599)
                                    ->setHelpText(tr('The HTTP method used for this request')))

                    ->add(DefinitionFactory::newComments($this, 'reason')
                                           ->setOptional(true)
                                           ->setReadonly(true)
                                           ->setSize(12)
                                           ->setMaxlength(255)
                                           ->setHelpText(tr('Reason why this request failed')))

                    ->add(DefinitionFactory::newUrl($this)
                                           ->setReadonly(true)
                                           ->setSize(12)
                                           ->setHelpText(tr('Request URL that failed')))

                    ->add(DefinitionFactory::newData($this, 'headers')
->setNoValidation(true)
                                           ->setOptional(true)
                                           ->setReadonly(true)
                                           ->setLabel(tr('Request headers'))
                                           ->setHelpText(tr('The HTTP headers that were received from the client for this request')))

                    ->add(DefinitionFactory::newData($this, 'cookies')
->setNoValidation(true)
                                           ->setOptional(true)
                                           ->setReadonly(true)
                                           ->setLabel(tr('Request cookies'))
                                           ->setHelpText(tr('The cookies that were received from the client for this request')))

                    ->add(DefinitionFactory::newData($this, 'post')
->setNoValidation(true)
                                           ->setOptional(true)
                                           ->setReadonly(true)
                                           ->setLabel(tr('Request POST data'))
                                           ->setHelpText(tr('The POST data that was  received from the client for this request')))

                    ->add(DefinitionFactory::newComments($this)
                                           ->setOptional(true)
                                           ->setHelpText(tr('Comments on this failed request')));
    }
}
