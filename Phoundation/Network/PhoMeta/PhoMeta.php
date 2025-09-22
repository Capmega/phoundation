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
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\PhoMeta;

use Phoundation\Core\Core;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryData;
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
     * @param IdentifierInterface|array|string|int|false|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = false)
    {
        parent::__construct($identifier);
        $this->setGlobalId(Core::getGlobalId());
    }


    /**
     * Returns a new PhoMeta object
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return static
     */
    public static function new(IdentifierInterface|array|string|int|false|null $identifier = false): static
    {
        return parent::new($identifier)->setGlobalId(Core::getGlobalId());
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
     * Returns the source of this PhoMeta object as an array
     *
     * @param bool $filter_meta
     * @param bool $filter_protected_columns
     *
     * @return array
     */
    public function getSource(bool $filter_meta = false, bool $filter_protected_columns = true, ): array
    {
        $source = parent::getSource($filter_meta);

        try {
            $source['data'] = Json::ensureDecoded($source['data']);

        } catch (Throwable $e) {
            if (array_get($source, 'data') === null) {
             // Encountered empty string

            } else {
                throw PhoMetaInvalidDataException::new(tr('Failed to decode PhoMeta source data'), $e);
            }
        }

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
            // The message is JSON and contains 'meta' and 'data'
            $message = $this->parsePhoMessage($message);

        } else {
            // Set hash based message
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
            throw PhoMetaVersionNotSupportedException::new(tr('Pho header message version ":version" contains unsupported method ":method"', [
                ':version' => $version,
                ':method'  => $method
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
                                  ->addData([
                                      'message' => $message
                                  ]);
        }

        foreach (['data', 'meta'] as $part) {
            if (!array_key_exists($part, $json)) {
                throw PhoMetaException::new(tr('The specified PhoMeta enabled message is missing the ":part" section', [
                    ':part' => $part
                ]))->addData([
                    'message' => $message
                ]);
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
     * @return static
     */
    public function addTest(PhoMetaTestInterface $test): static
    {
        $this->addData('test', $test->getSource(true));

        return $this;
    }


    /**
     * Adds a test value to this PhoMeta object's source
     *
     * @return PhoMetaTestInterface|null
     */
    public function getTest(): ?PhoMetaTestInterface
    {
        $data = $this->getData(true);
        $test = array_get($data,'test');

        if ($test) {
            if (is_string($test)) {
                $test = Json::decode($test);
            }

            return PhoMetaTest::newFromSource($test);
        }

        return null;
    }


    /**
     * Returns the value of this PhoMeta's PhoMetaTest's component
     *
     * @return string|null
     */
    public function getTestComponent(): ?string
    {
        return $this->getTest()?->getComponent();
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
     * @return static
     */
    public function addData(string $key, string|array $data, bool $data_is_sub_array = false): static
    {
        $this_data = $this->getData(true) ?? [];

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
     * @return static
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
     * Returns true if a test was found, false otherwise
     *
     * @param string|null $component
     *
     * @return bool
     */
    public function containsTest(?string $component = null): bool
    {
        $test_data = isset_get($this->getSource(true)['data']['test']);

        if (!isset($test_data)) {
            return false;
        }

        if ($component) {
            if ($test_data['component'] === $component) {
                return true;

            } else {
                return false;
            }
        }

        // No component specified, test data is set
        return true;
    }


    /**
     * Removes the PhoMetaTest data for a specified component, and records the test result in the required database.
     *
     * Returns true if a test was found, false otherwise
     *
     * @param string $component
     *
     * @return bool
     */
    public function processTestComponent(string $component): bool
    {
        $pho_meta_test = isset_get($this->getSource(true)['data']['test']);

        if ($pho_meta_test) {
            try {
                if ($pho_meta_test['component'] === $component) {
                    PhoMetaTest::newFromSource($pho_meta_test)->finish();
                    return true;
                }

            } catch (PhoMetaTestException $e) {
                throw PhoMetaException::new(tr('Failed to save PhoMetaTest Data'), $e);
            }
        }

        return false;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $o_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
    {
        $o_definitions->add(DefinitionFactory::newCode('global_id')
                                             ->setMaxLength(32)
                                             ->setLabel('Global request identifier'))

                      ->add(DefinitionFactory::newCode('hash')
                                             ->setMaxLength(64)
                                             ->setLabel('Message digest'))

                      ->add(DefinitionFactory::newData()
                                             ->setLabel('Message meta data')
// No validation for now until we can figure out how to validate the contents properly
->setNoValidation(true));

        return $this;
    }
}
