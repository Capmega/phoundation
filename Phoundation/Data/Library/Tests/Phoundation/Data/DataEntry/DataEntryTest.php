<?php

/**
 * Class TestDataEntry
 *
 * This PHPUnit test class will test the \Phoundation\Data\DataEntry Object
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Library\Tests\Phoundation\Data\DataEntry;

use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Exception\DataEntryColumnsNotDefinedException;
use Phoundation\Data\DataEntries\Exception\DataEntryInvalidIdentifierException;
use Phoundation\Data\DataEntries\Exception\DataEntryIsNewException;
use Phoundation\Data\DataEntries\Exception\DataEntryNoIdentifierSpecifiedException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotSavedException;
use Phoundation\Data\DataEntries\Tests\TestDataEntry;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use PHPUnit\Framework\TestCase;
use Throwable;

class DataEntryTest extends TestCase
{
    /**
     * Tests DataEntry::new()
     *
     * @return void
     */
    public function testNew()
    {
        $this->assertInstanceOf(DataEntry::class, DataEntry::new());
    }


    /**
     * Tests DataEntry::new()->getId()
     *
     * @return void
     */
    public function testNewId()
    {
        $this->assertEquals(null, DataEntry::new()->getId(false));
    }


    /**
     * Tests DataEntry::getDefaultMetaColumns()
     *
     * @return void
     */
    public function testGetDefaultMetaColumns()
    {
        $entry = DataEntry::new();
        $this->assertContains('id', $entry->getDefaultMetaColumns());
        $this->assertContains('created_on', $entry->getDefaultMetaColumns());
        $this->assertContains('created_by', $entry->getDefaultMetaColumns());
        $this->assertContains('meta_id', $entry->getDefaultMetaColumns());
        $this->assertContains('status', $entry->getDefaultMetaColumns());
        $this->assertContains('meta_state', $entry->getDefaultMetaColumns());
        $this->assertContains('modified_on', $entry->getDefaultMetaColumns());
        $this->assertContains('modified_by', $entry->getDefaultMetaColumns());
    }

    /**
     * Tests DataEntry::getIdColumn()
     *
     * @return void
     */
    public function testGetIdColumn()
    {
        $entry = TestDataEntry::new();

        $this->assertEquals('id', $entry->getIdColumn(), 'getIdColumn should return the "id" column');
    }


    /**
     * Tests DataEntry::setUniqueColumnValue()
     * Tests DataEntry::getUniqueColumnValue()
     *
     * @return void
     */
    public function testGetUniqueColumnValue()
    {
        $entry = TestDataEntry::new();
        $this->assertNull($entry->getUniqueColumnValue(), 'The TestDataEntry object should have no Unique Column Value');

        $value = 'unique-code';
        $entry->setUniqueColumnValue($value);
        $this->assertEquals($value, $entry->getUniqueColumnValue(), 'The TestDataEntry object should have the specified Unique Column Value');
    }

    /**
     * Tests DataEntry::set()
     *
     * @return void
     */
    public function testSet()
    {
        $entry = TestDataEntry::new();

        $test_value = 'test-value';

        // Test setting null value
        $entry->set(null, 'name');
        $this->assertEquals(null, $entry->get('name'), 'The TestDataEntry object should have the specified value');

        $entry->set('example-name', 'name');
        $entry->set(null, 'name', skip_null_values: true);
        $this->assertEquals('example-name', $entry->get('name'), 'The TestDataEntry object should have the specified value');

        // Test setting successfully
        $test_key = 'test_column';
        $entry->set($test_value, $test_key);
        $this->assertEquals($test_value, $entry->get($test_key), 'The TestDataEntry object should have the specified value');

        $this->expectException(DataEntryColumnsNotDefinedException::class);

        // Test setting to invalid column
        $entry->set($test_value, 'invalid-column');
    }


    /**
     * Tests DataEntry::get()
     *
     * @return void
     */
    public function testGet()
    {
        $entry = TestDataEntry::new();

        // Successful get
        $test_value = 'test_value';
        $test_key   = 'test_column';
        $entry->set($test_value, $test_key);

        $value = $entry->get($test_key);
        $this->assertEquals($test_value, $value, '`get()` should return the specified value');

        // Return null if no value set
        $entry->clear();
        $value = $entry->get($test_key, false);
        $this->assertNull($value, '`get()` should return the specified value');

        // Failure without exception
        $entry->clear();
        $test_key_invalid = 'test-get-value-invalid';
        $value = $entry->get($test_key_invalid, false);
        $this->assertNull($value, 'get() should return the specified value');

        // Failure with exception
        $this->expectException(OutOfBoundsException::class);
        $entry->get($test_key_invalid);
    }

    /**
     * Tests DataEntry::save()
     *
     * @return void
     */
    public function testSave()
    {
        $entry = TestDataEntry::new();

        // Successful save
        $code = Strings::getUuid();
        $test_value = 'test_value_' . $code;
        $test_key   = 'test_column';
        $entry->set($test_value, $test_key);
        $entry->setName('test_name_' . $code);
        $entry->save();

        $this->assertTrue($entry->isCreated());
        $this->assertTrue($entry->isInitialized());
        $this->assertTrue($entry->isSaved());
        $this->assertFalse($entry->isNew());
        $this->assertTrue((bool) $entry->getId(), '`getId()` should return a value');

        // Failure with exception (name is required)
        try {
            TestDataEntry::new()->save();
            $this->fail('Expected ValidationFailedException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(ValidationFailedException::class, $e);
        }
    }


    /**
     * Tests DataEntry::isNew()
     *
     * @return void
     */
    public function testIsNew()
    {
        $entry = TestDataEntry::new();
        $this->assertTrue($entry->isNew(), 'The TestDataEntry object that is not yet written to a database should be new');

        $entry->setName(Strings::getRandom(4) . Numbers::getRandomInt(1000,9999));
        $entry->save();
        $this->assertFalse($entry->isNew(), 'The TestDataEntry object that has been written to a database should NOT be new');
    }


    /**
     * Tests DataEntry::load()
     *
     * @return void
     */
    public function testLoad()
    {
        $name = Strings::getRandom(4) . Numbers::getRandomInt(1000,9999);
        $entry = TestDataEntry::new()->setName($name)->save();

        // Test load from ID
        $loaded_entry = TestDataEntry::new();
        $this->assertTrue($loaded_entry->isNew());
        $this->assertFalse($loaded_entry->isLoaded());

        $loaded_entry->load($entry->getId());
        $this->assertFalse($loaded_entry->isNew());
        $this->assertTrue($loaded_entry->isLoaded());
        $this->assertEquals($name, $loaded_entry->getName(), 'The loaded TestDataEntry should have the same name');

        // Test load from Name
        $loaded_entry_2 = TestDataEntry::new()->load(['name' => $name]);
        $this->assertEquals($entry->getId(), $loaded_entry_2->getId(), 'The loaded TestDataEntry should have the same ID');

        // Load from non-unique column should fail
        $test_value = Strings::getRandom(4) . Numbers::getRandomInt(1000,9999);
        TestDataEntry::new()->setName(Strings::getUuid())->set($test_value, 'test_column')->save();
        TestDataEntry::new()->setName(Strings::getUuid())->set($test_value, 'test_column')->save();

        try {
            TestDataEntry::new()->load(['test_column' => $test_value]);
            $this->fail('Expected SqlMultipleResultsException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(SqlMultipleResultsException::class, $e);
        }
    }


    /**
     * Tests DataEntry::loadNullOrNull()
     *
     * @return void
     */
    public function testLoadNull()
    {
        // Test null identifier EnumLoadParameter::null
        $test_1 = TestDataEntry::new()->loadNullOrNull();
        $this->assertNull($test_1);

        // Test load from ID
        $name = Strings::getRandom(4) . Numbers::getRandomInt(1000,9999);
        $entry = TestDataEntry::new()->setName($name)->save();
        $loaded_entry = TestDataEntry::new()->loadNullOrNull($entry->getId());
        $this->assertEquals($name, $loaded_entry->getName(), 'The loaded TestDataEntry should have the same name');

        // Test not exists EnumLoadParameter::null
        $test_4 = TestDataEntry::new()->load(['name' => Strings::getUuid()], EnumLoadParameters::null, EnumLoadParameters::null);
        $this->assertNull($test_4);
    }


    /**
     * Tests DataEntry::loadThisOrThis()
     *
     * @return void
     */
    public function testLoadThisOrThis()
    {
        // Test null identifier EnumLoadParameter::this
        $test_2 = TestDataEntry::new()->setName('test_name')->loadThisOrThis();
        $this->assertNull($test_2->getId(false), 'TestDataEntry should not have loaded with an ID');
        $this->assertEquals('test_name', $test_2->getName(), 'TestDataEntry should have the same name');

        // Test load from ID
        $name = Strings::getRandom(4) . Numbers::getRandomInt(1000,9999);
        $entry = TestDataEntry::new()->setName($name)->save();
        $loaded_entry = TestDataEntry::new()->loadThisOrThis($entry->getId());
        $this->assertEquals($name, $loaded_entry->getName(), 'The loaded TestDataEntry should have the same name');

        // Test not exists EnumLoadParameter::this
        $test_5 = TestDataEntry::new()->setTestColumn('test_value')->loadThisOrThis(['name' => Strings::getUuid()]);
        $this->assertNull($test_2->getId(false), 'TestDataEntry should not have loaded with an ID');
        $this->assertEquals('test_value', $test_5->getTestColumn(), 'TestDataEntry test column should have the same value');
    }


    /**
     * Tests DataEntry::loadExceptionOrException()
     *
     * @return void
     */
    public function testLoadExceptionOrException()
    {
        // Test load from ID
        $name = Strings::getRandom(4) . Numbers::getRandomInt(1000,9999);
        $entry = TestDataEntry::new()->setName($name)->save();
        $loaded_entry = TestDataEntry::new()->load($entry->getId(), EnumLoadParameters::exception, EnumLoadParameters::exception);
        $this->assertEquals($name, $loaded_entry->getName(), 'The loaded TestDataEntry should have the same name');

        // Test null identifier EnumLoadParameter::exception
        try {
            TestDataEntry::new()->load(null, EnumLoadParameters::exception, EnumLoadParameters::exception);
            $this->fail('Expected DataEntryNoIdentifierSpecifiedException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(DataEntryNoIdentifierSpecifiedException::class, $e);
        }

        // Test not exists EnumLoadParameter::exception
        try {
            TestDataEntry::new()->load(['name' => Strings::getUuid()], EnumLoadParameters::exception, EnumLoadParameters::exception);
            $this->fail('Expected DataEntryNotExistsException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(DataEntryNotExistsException::class, $e);
        }
    }


    /**
     * Tests DataEntry::loadRandom()
     *
     * @return void
     */
    public function testLoadRandom()
    {
        $entry = TestDataEntry::new()->loadRandom();
        $this->assertFalse($entry->isNew(), 'A randomly loaded entry should have information in its source');
        $this->assertNotEmpty($entry->getName());
        $this->assertTrue($entry->isInitialized());
    }


    /**
     * Tests DataEntry::getSource()
     *
     * @return void
     */
    public function testGetSource()
    {
        $test_entry = TestDataEntry::new()->setName(Strings::getUuid())->setTestColumn('test_value')->save();
        $test_source = $test_entry->getSource();

        $this->assertEquals('test_value', array_get_safe($test_source, 'test_column'));
        $this->assertTrue((bool) array_get_safe($test_source, 'id'));

        $test_source_2 = $test_entry->getSource(true);
        $this->assertEquals('test_value', array_get_safe($test_source_2, 'test_column'));
        $this->assertNull(array_get_safe($test_source_2, 'id'));

        $test_entry_2 = TestDataEntry::new();
        $this->assertArrayNotHasKey('id', $test_entry_2->getSource());
        $test_entry_2->initialize();
        $this->assertArrayHasKey('id', $test_entry_2->getSource());
    }


    /**
     * Tests DataEntry::setSource()
     *
     * @return void
     */
    public function testSetSource()
    {
        $name = Strings::getRandom(4) . Numbers::getRandomInt(1000,9999);
        TestDataEntry::new()->setName($name)->save();

        // Test setting a single column
        $test_entry = TestDataEntry::new();
        $this->assertFalse($test_entry->isInitialized());

        $test_entry->setSource(['seo_name' => $name, 'name' => $name, 'test_column' => 'test_value1']);
        $this->assertEquals($name, $test_entry->getName());
        $this->assertTrue($test_entry->isInitialized());
        $this->assertTrue($test_entry->isNew());

        // Test overwriting a column
        $test_entry->setSource(['test_column' => 'test_value2']);
        $this->assertNull($test_entry->getName());
        $this->assertEquals('test_value2', $test_entry->getTestColumn());

        // Test setting source with null
        $test_entry->setSource();
        $this->assertNull($test_entry->getTestColumn());

        // Test setting source with filter_meta: true
        $name = Strings::getRandom(4) . Numbers::getRandomInt(1000,9999);
        $test_entry_2 = TestDataEntry::new()->setName($name)->save();
        $test_entry_3 = TestDataEntry::new();
        $test_entry_3->setSource($test_entry_2->getSource(), filter_meta: true);
        $this->assertNull($test_entry_3->getId(false));

        // Test setting source and validating
        $test_entry_2->setSource($test_entry->getSource());
        $this->assertTrue($test_entry_2->isValidated());

        // Test setting source with non-existing column
        try {
            TestDataEntry::new()->setSource(['invalid_column' => 'value']);
            $this->fail('Expected DataEntryInvalidIdentifierException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(DataEntryInvalidIdentifierException::class, $e);
        }
    }


    /**
     * Tests DataEntry::setSourceDirect()
     *
     * @return void
     */
    public function testSetSourceDirect()
    {
        $name = Strings::getRandom(4) . Numbers::getRandomInt(1000,9999);

        // Test setting source with non-existing column
        $test_entry = TestDataEntry::new()->setSourceDirect(['invalid_column' => 'value', 'seo_name' => $name]);
        $this->assertEquals('value', $test_entry->get('invalid_column', false));
        $this->assertFalse($test_entry->isValidated());

        // Test setting source and validating
        $test_entry_2 = TestDataEntry::new()->setName($name)->save();
        $test_entry_2->setSourceDirect($test_entry->getSource());
        $this->assertFalse($test_entry_2->isValidated());

        // Test setting source with non-existing column AND no unique identifier
        try {
            TestDataEntry::new()->setSourceDirect(['invalid_column' => 'value']);
            $this->fail('Expected DataEntryInvalidIdentifierException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(DataEntryInvalidIdentifierException::class, $e);
        }
    }


    /**
     * Tests DataEntry::delete()
     *
     * @return void
     */
    public function testDelete()
    {
        $test_entry = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $test_entry->delete();
        $this->assertEquals('deleted', $test_entry->get('status'));
        $this->assertTrue($test_entry->isDeleted());
        $this->assertNull($test_entry->getUniqueColumnValue(false));

        try {
            TestDataEntry::new()->delete();
            $this->fail('Expected DataEntryIsNewException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(DataEntryIsNewException::class, $e);
        }

        try {
            $test_entry_2 = TestDataEntry::new()->setName(Strings::getUuid())->save();
            $test_entry_2->setTestColumn(Strings::getUuid())->delete();
            $this->fail('Expected OutOfBoundsException was not thrown since object was modified then deleted');
        } catch (Throwable $e) {
            $this->assertInstanceOf(OutOfBoundsException::class, $e);
        }
    }


    /**
     * Tests DataEntry::erase()
     *
     * @return void
     */
    public function testErase()
    {
        $test_entry = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $test_entry->erase();
        $this->assertNull($test_entry->getStatus());

        try {
            TestDataEntry::new()->erase();
            $this->fail('Expected DataEntryIsNewException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(DataEntryIsNewException::class, $e);
        }
    }


    /**
     * Tests DataEntryCore::setStatus())
     *
     * @return void
     */
    public function testSetStatus()
    {
        $test_entry = TestDataEntry::new();
        $this->assertNull($test_entry->getStatus());
        $this->assertTrue($test_entry->isStatus(null));

        $test_entry->setStatus('test');
        $this->assertEquals('test', $test_entry->getStatus());
        $this->assertTrue($test_entry->isStatus('test'));
    }


    /**
     * Tests DataEntryCore::construct()
     *
     * @return void
     */
    public function testConstruct()
    {
        $test_data_entry = TestDataEntry::new();
        $this->assertFalse($test_data_entry->isInitialized());

        $name = Strings::getUuid();
        $test_data_entry->setName($name)->save();
        $id = $test_data_entry->getId();

        $test_data_entry_2 = TestDataEntry::new(['name' => $name]);
        $this->assertEquals($id, $test_data_entry_2->getId());
    }


    /**
     * Tests DataEntryCore::destruct()
     *
     * @return void
     */
    public function testDestruct()
    {
        // TODO
        $this->assertTrue(true);
    }


    /**
     * Tests DataEntryCore::initialize()
     *
     * @return void
     */
    public function testInitialize()
    {
        $test_data_entry = TestDataEntry::new();
        $this->assertEmpty($test_data_entry->getSource());

        $test_data_entry->initialize();
        $this->assertTrue($test_data_entry->isInitialized());
        $this->assertTrue(!(empty($test_data_entry->getSource())));
        $this->assertContains('id', $test_data_entry->getSourceKeys());
        $this->assertEmpty($test_data_entry->getId(false));

        $test_data_entry_2 = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $id = $test_data_entry_2->getId();

        $test_data_entry_3 = TestDataEntry::new($id);
        $this->assertTrue($test_data_entry_3->isInitialized());
        $this->assertTrue(!(empty($test_data_entry_3->getSource())));
        $this->assertTrue((bool) $test_data_entry_3->getId(false));
    }


    /**
     * Tests DataEntryCore::__toInteger()
     *
     * @return void
     */
    public function testToInteger()
    {
        $test_data_entry = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $id = $test_data_entry->getId();
        $this->assertEquals($id, $test_data_entry->__toInteger());
    }


    /**
     * Tests DataEntryCore::__clone()
     *
     * @return void
     */
    public function testClone()
    {
        $test_data_entry = TestDataEntry::new()->setName(Strings::getUuid());
        $clone = clone $test_data_entry;
        $this->assertNull($clone->getId(false));
        $this->assertEquals($test_data_entry->getName(), $clone->getName());
    }


    /**
     * Tests DataEntryCore::getUniqueColumn()
     *
     * @return void
     */
    public function testGetUniqueColumn()
    {
        $this->assertEquals('seo_name', TestDataEntry::getUniqueColumn());
    }


    /**
     * Tests DataEntryCore::getEntryName()
     *
     * @return void
     */
    public function testGetEntryName()
    {
        $this->assertEquals(tr('Test DataEntry'), TestDataEntry::getEntryName());
    }


    /**
     * Tests DataEntryCore::getTable()
     *
     * @return void
     */
    public function testGetTable()
    {
        $this->assertEquals('test_dataentries', TestDataEntry::getTable());
    }


    /**
     * Tests DataEntryCore::isNotNew()
     *
     * @return void
     */
    public function testIsNotNew()
    {
        $test_data_entry = TestDataEntry::new()->setName(Strings::getUuid());
        $this->assertFalse($test_data_entry->isNotNew());
        $test_data_entry->save();
        $this->assertTrue($test_data_entry->isNotNew());
    }


    /**
     * Tests DataEntryCore::getId()
     *
     * @return void
     */
    public function testGetId()
    {
        $test_data_entry = TestDataEntry::new()->setName(Strings::getUuid());
        $this->assertNull($test_data_entry->getId(false));

        try {
            $test_data_entry->getId();
            $this->fail('Expected DataEntryNotSavedException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(DataEntryNotSavedException::class, $e);
        }

        $test_data_entry->save();
        $this->assertTrue((bool) $test_data_entry->getId());
        $this->assertEquals($test_data_entry->getId() . '_test', $test_data_entry->getId(suffix: '_test'));
    }


    /**
     * Tests DataEntryCore::getDisplayId()
     *
     * @return void
     */
    public function testGetDisplayId()
    {
        $test_data_entry = TestDataEntry::new()->setName(Strings::getUuid());
        $this->assertEquals('[NEW]', $test_data_entry->getDisplayId());
        $test_data_entry->save();
        $this->assertEquals($test_data_entry->getId(), $test_data_entry->getDisplayId());
    }


    /**
     * Tests DataEntryCore::getLogId()
     *
     * @return void
     */
    public function testGetLogId()
    {
        $name = Strings::getUuid();
        $test_data_entry = TestDataEntry::new()->setName($name);
        $this->assertEquals(ts('N/A') . ' / ' . $name, $test_data_entry->getLogId());
        $test_data_entry->save();
        $this->assertEquals($test_data_entry->getId() . ' / ' . $name, $test_data_entry->getLogId());
    }


    /**
     * Tests DataEntryCore::getSourceKeys()
     *
     * @return void
     */
    public function testGetSourceKeys()
    {
        $test_entry = TestDataEntry::new();
        $this->assertEmpty($test_entry->getSourceKeys());

        $test_entry->initialize();
        $this->assertNotEmpty($test_entry->getSourceKeys());
        $keys = ['id', 'created_on', 'created_by', 'meta_id', 'status', 'meta_state', 'name', 'seo_name', 'test_column'];
        foreach ($keys as $key) {
            $this->assertContains($key, $test_entry->getSourceKeys());
        }
    }


    /**
     * Tests DataEntryCore::reload()
     *
     * @return void
     */
    public function testReload()
    {
        $this->assertTrue(true);
        // TODO
    }


    /**
     * Tests DataEntryCore::loadColumns()
     *
     * @return void
     */
    public function testLoadColumns()
    {
        $this->assertTrue(true);
        // TODO
    }


    /**
     * Tests DataEntryCore::getClassName()
     *
     * @return void
     */
    public function testGetClassName()
    {
        $this->assertEquals('TestDataEntry', TestDataEntry::new()->getClassName());
    }


    /**
     * Tests DataEntryCore::idColumnIs()
     *
     * @return void
     */
    public function testIdColumnIs()
    {
        $this->assertTrue(TestDataEntry::idColumnIs('id'));
        $this->assertFalse(TestDataEntry::idColumnIs('seo_name'));
        $this->assertFalse(TestDataEntry::idColumnIs('name'));
    }
}
