<?php

/**
 * Class TestDataEntry
 *
 * This PHPUnit test class will test the \Phoundation\Data\DataEntry Object
 *
 * This PHPUnit test class will test itself against the system database table developer_unittests
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Library\Tests\Phoundation\Data\DataEntry;

use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Exception\DataEntryException;
use Phoundation\Data\DataEntries\Tests\TestDataEntry;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Numbers;
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
        $this->assertEquals(null, DataEntry::new()->getId());
    }


    //TODO: fix
//    /**
//     * Tests DataEntry::new(RANDOM_ID)
//     *
//     * @return void
//     */
//    public function testNewRandomId()
//    {
//        $id    = Numbers::getRandomInt();
//        $entry = TestDataEntry::new($id);
//
//        $entry->save();
//
//        $this->assertEquals($id, $entry->getId(), 'The TestDataEntry object should have the ID it was initialized with');
//    }


    /**
     * Tests DataEntry::getDefaultMetaColumns()
     *
     * @return void
     */
    public function testGetDefaultMetaColumns()
    {
        $entry = DataEntry::new();
        $expectedColumns = ['id', 'created_on', 'created_by', 'meta_id', 'status', 'meta_state'];

        $this->assertEquals($expectedColumns, $entry->getDefaultMetaColumns(), 'getDefaultMetaColumns should return default metadata columns');
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


    //TODO: fix
//    /**
//     * Tests DataEntry::isNew()
//     *
//     * @return void
//     */
//    public function testIsNew()
//    {
//        $entry = TestDataEntry::new();
//
//        $this->assertTrue($entry->isNew(), 'The TestDataEntry object that is not yet written to a database should be new');
//
//        $entry->setStatus('saved');
//
//        $this->assertFalse($entry->isNew(), 'The TestDataEntry object that has been written to a database should NOT be new');
//    }


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

        $test_value = 'test-set-value';

        // Test setting to invalid column
        try {
            $entry->set($test_value, 'invalid-column');

        } catch (Throwable $e) {
            $this->AssertEquals(DataEntryException::class, $e::class, 'A DataEntryException should have been thrown');
        }

        // Test setting to ignored column // TODO: add ignored column

        // Test setting successfully
        $test_key   = 'test_column';
        $entry->set($test_value, $test_key);

        $this->assertEquals($test_value, $entry->get($test_key), 'The TestDataEntry object should have the specified value');
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
        $test_value = 'test-get-value';
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
        $this->assertNull($value, '`get()` should return the specified value');

        // Failure with exception
        $this->expectException(OutOfBoundsException::class, '`get()` should return the specified value');
        $value = $entry->get($test_key_invalid, exception: true);
    }


    /**
     * Tests DataEntry::getTypesafe()
     *
     * @return void
     */
    public function testGetMetaColumns()
    {
        $entry = TestDataEntry::new();

        $meta_columns = ['id', 'created_on', 'created_by', 'meta_id', 'status', 'meta_state'];

        $this->assertEquals($meta_columns, $entry->getMetaColumns(), 'getMetaColumns should return meta data columns');
    }


    /**
    * Tests DataEntry::getSourceKeys()
    *
    * @return void
    */
    public function testGetSourceKeys()
    {
        $entry = TestDataEntry::new();

        $source_keys_unfiltered = ['id', 'created_on', 'created_by', 'meta_id', 'status', 'meta_state', 'seo_name', 'name', 'test_column', 'parents_id', 'description'];
        $source_keys_filtered   = ['seo_name', 'name', 'test_column', 'parents_id', 'description'];

        $this->AssertEquals($source_keys_unfiltered, $entry->getSourceKeys(), 'getSourceKeys() should return the source keys, including meta columns');
        $this->AssertEquals($source_keys_filtered, array_values($entry->getSourceKeys(true)), 'getSourceKeys() should return the filtered source keys, not including meta columns');
    }
}
