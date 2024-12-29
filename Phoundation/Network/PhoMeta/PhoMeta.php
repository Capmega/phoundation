<?php

/**
 * Class PhoMeta
 *
 * This class manages Phoundation object metadata.
 *
 * Objects can be any kind of information being sent to, or received from external parties, internal components, etc. The
 * metadata contains information about from the second the message was received all the way until it was stored in the
 * database. The meta information can travel over multiple processes, multiple servers, and still contain all
 * information
 *
 * @todo      Revise this class, it has multiple open issues. PhoMeta::parsePhoMessage() and PhoMeta::parsePhoMessageV1() make no sense.
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\PhoMeta;

use PDOStatement;
use Phoundation\Core\Core;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryData;
use Phoundation\Network\PhoMeta\Exceptions\PhoMetaException;
use Phoundation\Network\PhoMeta\Exceptions\PhoMetaInvalidDataException;
use Phoundation\Network\PhoMeta\Exceptions\PhoMetaTestException;
use Phoundation\Network\PhoMeta\Exceptions\PhoMetaVersionNotSupportedException;
use Phoundation\Network\PhoMeta\Interfaces\PhoMetaInterface;
use Phoundation\Network\PhoMeta\Interfaces\PhoMetaTestInterface;
use Phoundation\Utils\Json;
use Throwable;


class PhoMeta extends DataEntry implements PhoMetaInterface
{
    use TraitDataEntryData {
        setData as protected __setData;
    }


    /**
     * PhoMeta class constructor
     *
     * @param int|array|string|DataEntryInterface|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(int|array|string|DataEntryInterface|null $identifier = null, ?bool $meta_enabled = null, bool $init = true) {

        parent::__construct($identifier, $meta_enabled, $init);
        $this->setGlobalId(Core::getGlobalId());
    }


    /**
     * Returns a new PhoMeta object
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     *
     * @return static
     */
    public static function new(int|array|string|DataEntryInterface|null $identifier = null, ?bool $meta_enabled = null, bool $init = true): static
    {
        return parent::new($identifier, $meta_enabled, $init)->setGlobalId(Core::resetGlobalId());
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
     * Returns true if the given string has a PhoMeta Header
     *
     * @param string $message
     *
     * @return bool
     */
    public static function hasPhoMetaHeader(string $message): bool
    {
        return (bool) preg_match('/PHO\d/', $message);
    }


    /**
     * @param bool $filter_meta
     *
     * @return array
     */
    public function getSource(bool $filter_meta = false): array
    {
        $source = parent::getSource($filter_meta);

        try {
            $data = Json::ensureDecoded($source['data']);

        } catch (Throwable $e) {
            Throw PhoMetaInvalidDataException::new(tr('Error decoding this PhoMeta source'))
                                             ->addData($e);
        }

        $source['data'] = $data;
        return $source;
    }


    /**
     * Calls an existing 'extraction' method based on pho version
     *
     * @param string $message
     *
     * @return static
     */
    public function extractPhoMetaData(string $message): string
    {
        if (PhoMeta::hasPhoMetaHeader($message)) {
            // Message is json and contains 'meta' and 'data'
            $message = $this->parsePhoMessage($message);

        } else {
            // Set hash based on HL7 message
            $this->setHash(hash('sha256', $message));
        }

        return $message;
    }


    /**
     * Calls an existing 'extraction' method based on message pho version header, then parses the PhoMeta message and
     * populates this object source with the meta information
     *
     * @param string $message
     *
     * @return static
     *
     * @see PhoMeta::parsePhoMessageV1()
     */
    protected function parsePhoMessage(string $message): string
    {
        // Remove 4 byte PHO# version header
        $version = substr($message, 3, 1);
        $message = substr($message, 4);
        $method  = 'parsePhoMessageV' . $version;

        if (!method_exists($this, $method)) {
            throw PhoMetaVersionNotSupportedException::new(tr('Pho Header Message version ":version" is not supported', [
                ':version' => $version
            ]));
        }

        return $this->$method($message);
    }


    /**
     * Parses the specified PhoMeta enabled message and populates this object source with the meta information
     *
     * Message is required to be a JSON string with array content that contains the sections "meta" and "data"
     *
     * Returns the "data" section of the message as a string
     *
     * @param string $message
     *
     * @return string
     */
    protected function parsePhoMessageV1(string $message): string
    {
        try {
            $json = Json::decode($message);

        } catch (Throwable $e) {
            throw PhoMetaException::new(tr('Specified PhoMeta enabled message could not be JSON decoded and is likely invalid'), $e)
                                  ->addData(['message' => $message]);
        }

        foreach (['data', 'meta'] as $part) {
            if (!array_key_exists($part, $json)) {
                throw PhoMetaException::new(tr('The specified PhoMeta enabled message is missing the ":part" section', [
                    ':part' => $part
                ]))->addData(['message' => $message]);
            }
        }

        $this->setSource($json['meta'])
             ->setHash(hash('sha256', $json['data']));

        return $json['data'];
    }


    /**
     * Returns the hash for this object
     *
     * @return string|null
     */
    public function getHash(): string|null
    {
        return $this->getTypesafe('string', 'hash');
    }


    /**
     * Sets the hash for this object
     *
     * @param string|null $hash
     *
     * @return static
     */
    public function setHash(string|null $hash): static
    {
        return $this->set($hash, 'hash');
    }


    /**
     * Returns the global_id for this object
     *
     * @return string|null
     */
    public function getGlobalId(): string|null
    {
        return $this->getTypesafe('string', 'global_id');
    }


    /**
     * Sets the global_id for this object
     *
     * @param string|null $global_id
     *
     * @return static
     */
    public function setGlobalId(string|null $global_id): static
    {
        if ($global_id === null) {
            return $this;
        }

        Core::setGlobalId($global_id);

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
        $this->addData('test', $test->getSource(true));

        return $this;
    }


    /**
     * Adds an array of data to this PhoMeta object's source
     *
     * @param string       $key
     * @param string|array $data
     * @param bool         $data_is_sub_array Whether the data is stored as a sub array. If it is, the data will be
     *                                        stored inside the key=>value[], otherwise it will be stored as the
     *                                        key=>value
     *
     * @return $this
     */
    public function addData(string $key, string|array $data, bool $data_is_sub_array = false): static
    {
        $this_data = $this->getData() ?? [];

        if (is_string($data)) {
            $this_data[$key] = $data;

        } else {
            $this_data[$key] = empty($this_data[$key]) ? $data : $this->mergeData($data, $this_data[$key]);
        }

        return $this->setData($this_data);
    }


    /**
     * Sets the Data property
     *
     * @param array|string|null $data
     *
     * @return $this
     */
    public function setData(array|string|null $data): static
    {
        if (is_string($data)) {
            try {
                $data_array = Json::decode($data);

            } catch (Throwable) {
                // Data was not Json encoded, set it as array
                $data_array[] = $data;
            }

        } else {
            $data_array = $data;
        }

        return $this->__setData($data_array);
    }


    /**
     * Recursively copies the specified new data structure into the specified existing source data structure
     *
     * @param array $data
     * @param array $source
     *
     * @return array
     */
    protected function mergeData(array $data, array $source): array
    {
        foreach ($data as $key => $value) {

            if (array_key_exists($key, $source) and is_array($source[$key])) {
                $source[$key] = $this->mergeData($value, $source[$key]);

            } else {
                $source[$key] = $value;
            }
        }

        return $source;
    }


    /**
     * Checks the source for PhoMetaTest info and if it matches a specified component.
     *
     * If it does, it will remove that PhoMetaTest, and record the test result in the required database.
     *
     * Returns true if a test was found, false otherwise
     *
     * @param string $component
     *
     * @return bool
     */
    public function processTest(string $component): bool
    {
        $test_data = isset_get($this->getSource(true)['data']['test']);

        if ($test_data === null) {
            return false;
        }

        try {
            if ($test_data['component'] === $component) {
                PhoMetaTest::new($test_data)->finish();
                return true;
            }

        } catch (PhoMetaTestException $e) {
            throw PhoMetaException::new(tr('Failed to save PhoMetaTest Data'))
                                  ->addData($e);
        }

        return false;
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

                    ->add(DefinitionFactory::newCode($this, 'hash')
                                           ->setMaxlength(64)
                                           ->setLabel('Message digest'))

                    ->add(DefinitionFactory::newData($this)
                                           ->setLabel('Message meta data'));
    }
}
