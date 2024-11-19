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

use PDOStatement;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryData;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Network\PhoMeta\Exceptions\PhoMetaVersionNotSupportedException;
use Phoundation\Network\PhoMeta\Exceptions\SourceNotPhoundationMetaException;
use Phoundation\Network\PhoMeta\Interfaces\PhoMetaTestInterface;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Numbers;

class PhoMeta extends DataEntry
{
    use TraitDataEntryData;


    /**
     * The length of the headers in string format
     *
     * @var int $headers_string_length
     */
    protected int $headers_string_length;

    /**
     * The headers in string (json) format
     *
     * @var string $headers_string
     */
    protected string $headers_string;


    /**
     * PhoMeta class constructor
     *
     * @param int|array|string|DataEntryInterface|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(int|array|string|DataEntryInterface|null $identifier = null, ?bool $meta_enabled = null, bool $init = true) {
        parent::__construct($identifier, $meta_enabled, $init);

        $this->setLocalId(Core::getLocalId())
             ->setGlobalId(Core::getGlobalId())
             ->setPhoundation(1);
    }


    /**
     * Calls an existing 'extraction' method based on pho version
     *
     * @param string $message
     *
     * @return static
     */
    public function extractMetaHeader(string $message): string
    {
        if (PhoMeta::hasPhoMetaHeader($message)) {
            $this->setMetaHeader($message);
            $message = substr($message, $this->getHeadersStringLength());
        }

        $this->setHash(hash('sha256', $message));

        return $message;
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
        return $this->setHeadersString($message)
                    ->setGlobalId(Core::getGlobalId())
                    ->processFromString($this->getHeadersString());
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
     * Loads the specified data into this PhoMeta object
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     * @param array|null                                       $execute
     *
     * @return $this
     */
    public function setSource(IteratorInterface|array|string|PDOStatement|null $source = null, ?array $execute = null): static
    {
        // Validate "phoundation" key available
        if (!array_key_exists('phoundation', $source)) {
            Incident::new()
                    ->setSeverity(EnumSeverity::medium)
                    ->setTitle(tr('Phoundation metadata missing'))
                    ->setBody(tr('Specified Phoundation metadata source contains no required "phoundation" key'))
                    ->setData(['source' => $source])
                    ->setNotifyRoles('developer')
                    ->save()
                    ->throw(SourceNotPhoundationMetaException::class);
        }


        // Validate that the "phoundation" key contains as a value a registered, authorized key
        // TODO IMPLEMENT
        return parent::setSource($source, $execute);
    }


    /**
     * Returns this PhoMeta object's headers string length
     *
     * @return int
     */
    public function getHeadersStringLength(): int
    {
        return $this->headers_string_length;
    }


    /**
     * Sets the headers string length
     *
     * @param int $length
     *
     * @return static
     */
    protected function setHeadersStringLength(int $length): static
    {
        $this->headers_string_length = $length;
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
     * Sets the PhoMeta headers in string format given an entire message
     *
     * @param string $message
     *
     * @return PhoMeta
     */
    public function setHeadersString(string $message): static
    {
        $this->setHeadersStringLength(Numbers::binaryToInt(substr($message, 4, 4)));
        $this->headers_string = substr($message, 0, $this->getHeadersStringLength());

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
    public static function getEntryName(): string
    {
        return tr('Phoundation metadata');
    }


    /**
     * Returns the hash for this object
     *
     * @return string|int|null
     */
    public function getHash(): string|int|null
    {
        return $this->getTypesafe('string', 'hash');
    }


    /**
     * Sets the hash for this object
     *
     * @param string|int|null $hash
     *
     * @return static
     */
    public function setHash(string|int|null $hash): static
    {
        return $this->set($hash, 'hash');
    }


    /**
     * Returns the phoundation key for this object
     *
     * @return string|int|null
     */
    public function getPhoundation(): string|int|null
    {
        return $this->getTypesafe('string', 'phoundation');
    }


    /**
     * Sets the phoundation key for this object
     *
     * @param string|int|null $value
     *
     * @return static
     */
    public function setPhoundation(string|int|null $value): static
    {
        return $this->set($value, 'phoundation');
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
        // Only override global ID if the message contained on
        if ($this->getGlobalId() and ($global_id === null)) {
            return $this;
        }

        return $this->set($global_id, 'global_id');
    }


    /**
     * Adds a test value to this PhoMeta object's source
     *
     * @param PhoMetaTestInterface $test
     *
     * @return $this
     */
    public function addTest(PhoMetaTestInterface $test): static
    {
        $this->addData('tests', $test->getSource(true));
        $this->addData('mock key', ['v1','v2']);    //currently, values do not go into the key, they go into data[]
//        return $this->addData('tests', $test->getSource());
        return $this;
    }


    /**
     * Adds an array of data to this PhoMeta object's source
     *
     * @param string $key
     * @param array  $data
     *
     * @return $this
     */
    public function addData(string $key, array $data): static
    {
        if (empty($this->source['data'])){
            $this->source['data'] = [];
        }

        if (empty($this->source['data'][$key])){
            $this->source['data'][$key] = [];
        }

        return $this->mergeData($data, $this->source['data'][$key]);
    }


    /**
     * Recursively copies the specified new data structure into the specified existing source data structure
     *
     * @param array $data
     * @param array $source
     *
     * @return $this
     */
    protected function mergeData(array $data, array $source): static
    {
        show($data);
        foreach ($data as $key => $value) {

            if (array_key_exists($key, $source)) {
                $source[$key] = $this->mergeData($value, $source[$key]);

            } else {
                $this->source['data'][$key] = $value;
            }
        }

        return $this;
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

                    ->add(DefinitionFactory::newCode($this, 'hash')
                                           ->setMaxlength(64)
                                           ->setLabel('Message digest'))

                    ->add(DefinitionFactory::newData($this)
                                           ->setLabel('Message meta data'))

                    ->add(DefinitionFactory::newCode($this, 'phoundation')
                                           ->setMaxlength(64)
                                           ->setLabel('Phoundation'));
    }
}
