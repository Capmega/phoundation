<?php

/**
 * Class PhoMeta
 *
 * This class manages Phoundation object metadata.
 *
 * Objects can be any kind of information being sent to, or received from external parties, internal services, etc. The
 * metadata contains information about from the second the message was received all the way until it was stored in the
 * database. The meta information can travel over multiple processes, multiple servers, and still contain all
 * information
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Network\PhoMeta;

use Phoundation\Core\Core;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryData;
use Phoundation\Network\PhoMeta\Exceptions\PhoMetaVersionNotSupportedException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Numbers;

class PhoMeta extends DataEntry
{
    use TraitDataEntryData;

    
    /**
     * The length of the headers in string format
     *
     * @var int $headers_string_len
     */
    protected int $headers_string_len;

    /**
     * The headers in string (json) format
     *
     * @var string $message_without_headers
     */
    protected string $headers_string;


    /**
     * Calls an existing 'extraction' method based on pho version
     *
     * @param string $message
     *
     * @return static
     */
    public function extractMetaHeader(string $message): string
    {
        $this->setMetaHeader($message);
        
        return substr($message, $this->getHeadersStringLen());
    }


    /**
     * Calls an existing 'extraction' method based on pho version
     *
     * @param string $message
     *
     * @return static
     */
    protected function setMetaHeader(string $message): static
    {
        $version = substr($message, 3, 1);
        $method  = 'parsePhoMessageV' . $version;

        if (!method_exists($this, $method)) {
            throw PhoMetaVersionNotSupportedException::new(tr('Pho Header Message version ":version" is not supported', [
                ':version' => $version
            ]));
        }

        return $this->$method($method);
    }


    /**
     * Populates this PhoMeta's source array with correct details based on message
     *
     * @param string $message
     *
     * @return PhoMeta
     */
    protected function parsePhoMessageV1(string $message): static
    {

        $this->setHeadersStringLen(Numbers::binaryToInt(substr($message, 4, 4)))
             ->setHeadersString(substr($message, 0, $this->getHeadersStringLen()))
             ->setGlobalId(Core::getGlobalId());

        return $this->processFromString($this->getHeadersString());
    }


    /**
     * Populates this PhoMeta object's source with the JSON string formatted message header
     *
     * @param string $headers
     *
     * @return $this
     */
    protected function processFromString(string $headers): static
    {
        return $this->setSource(Json::decode($headers));
    }


    /**
     * Returns this PhoMeta object's headers string length
     *
     * @return int
     */
    public function getHeadersStringLen(): int
    {
        return $this->headers_string_len;
    }


    /**
     * Sets the headers string length
     *
     * @param int $length
     *
     * @return static
     */
    public function setHeadersStringLen(int $length): static
    {
        $this->headers_string_len = $length;
        return $this;
    }


    /**
     * Returns the PhoMeta headers in string format
     *
     * @return string
     */
    public function getHeadersString(): string
    {
        return $this->headers_string;
    }


    /**
     * Sets the PhoMeta headers in string format
     *
     * @param string $headers
     *
     * @return PhoMeta
     */
    public function setHeadersString(string $headers): static
    {
        $this->headers_string = $headers;
        return $this;
    }


    /**
     * Returns true if the given string has a PhoMeta Header
     *
     * @param string $message
     *
     * @return bool
     */
    public static function hasPhoMetaHeader(string $message): bool
    {
        return (str_starts_with($message, 'PHO'));
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'network_meta';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Phoundation metadata');
    }


    /**
     * Returns the code for this object
     *
     * @return string|int|null
     */
    public function getCode(): string|int|null
    {
        return $this->getTypesafe('string', 'code');
    }


    /**
     * Sets the code for this object
     *
     * @param string|int|null $code
     *
     * @return static
     */
    public function setCode(string|int|null $code): static
    {
        return $this->set($code, 'code');
    }


    /**
     * Returns the local_id for this object
     *
     * @return string|int|null
     */
    public function getLocalId(): string|int|null
    {
        return $this->getTypesafe('string', 'local_id');
    }


    /**
     * Sets the local_id for this object
     *
     * @param string|int|null $local_id
     *
     * @return static
     */
    public function setLocalId(string|int|null $local_id): static
    {
        return $this->set($local_id, 'local_id');
    }


    /**
     * Returns the global_id for this object
     *
     * @return string|int|null
     */
    public function getGlobalId(): string|int|null
    {
        return $this->getTypesafe('string', 'global_id');
    }


    /**
     * Sets the global_id for this object
     *
     * @param string|int|null $global_id
     *
     * @return static
     */
    public function setGlobalId(string|int|null $global_id): static
    {
        return $this->set($global_id, 'global_id');
    }



    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::newCode($this, 'global_id')
                                           ->setMaxlength(32)
                                           ->setLabel('Global request identifier'))

                    ->add(DefinitionFactory::newCode($this, 'local_id')
                                           ->setMaxlength(32)
                                           ->setLabel('Local request identifier'))

                    ->add(DefinitionFactory::newData($this)
                                           ->setLabel('Message meta data'));
    }
}