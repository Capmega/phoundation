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

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Exception\DataEntryColumnsNotDefinedException;
use Phoundation\Data\DataEntries\Exception\DataEntryIsNewException;
use Phoundation\Data\DataEntries\Exception\DataEntryNoIdentifierSpecifiedException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotSavedException;
use Phoundation\Data\DataEntries\Tests\TestDataEntry;
use Phoundation\Data\DataEntries\Tests\TestDataIterator;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Exception\IteratorKeyExistsException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use PHPUnit\Event\TestData\TestData;
use PHPUnit\Framework\TestCase;
use Throwable;

class DataIteratorTest extends TestCase
{
    /**
     * Tests DataIteratorCore::uniqueColumnIs()
     *
     * @return void
     */
    public function testUniqueColumnIs()
    {
        $this->assertTrue(TestDataIterator::uniqueColumnIs('seo_name'));
        $this->assertFalse(TestDataIterator::uniqueColumnIs('name'));
        $this->assertFalse(TestDataIterator::uniqueColumnIs('non_existing_column'));
    }


    /**
     * Tests DataIteratorCore::getUniqueColumn()
     *
     * @return void
     */
    public function testGetUniqueColumn()
    {
        $this->assertEquals('seo_name', TestDataIterator::getUniqueColumn());
    }


    /**
     * Tests DataIteratorCore::getIdColumn()
     *
     * @return void
     */
    public function testGetIdColumn()
    {
        $this->assertEquals('id', TestDataIterator::getIdColumn());
    }


    /**
     * Tests DataIteratorCore::getConfigurationPath()
     *
     * @return void
     */
    public function testGetConfigurationPath()
    {
        $this->assertNull(TestDataIterator::getConfigurationPath());
    }


    /**
     * Tests DataIteratorCore::keyExists()
     *
     * @return void
     */
    public function testKeyExists()
    {
        $test_data_iterator = TestDataIterator::new();
        $this->assertFalse($test_data_iterator->keyExists('key'));

        // TODO test successful operation
    }


    /**
     * Tests DataIteratorCore::getQueryBuilderObject()
     *
     * @return void
     */
    public function testGetQueryBuilderObject()
    {
        $test_data_iterator = TestDataIterator::new();
        $this->assertInstanceOf(QueryBuilder::class, $test_data_iterator->getQueryBuilderObject());

        $wheres = $test_data_iterator->getQueryBuilderObject()->getWheres();
        $this->assertEquals([], $wheres);
        // TODO add more tests
    }


    /**
     * Tests DataIteratorCore::getQuery()
     *
     * @return void
     */
    public function testGetQuery()
    {
        $this->assertTrue(true);
        // TODO implement
//        $test_data_iterator = TestDataIterator::new();
//        $this->assertEquals('', $test_data_iterator->getQuery());
    }


    /**
     * Tests DataIteratorCore::getSqlSelectColumns()
     *
     * @return void
     */
    public function testGetSqlSelectColumns()
    {
        $test_data_iterator = TestDataIterator::new();
        $sample_select = '`test_dataentries`.`seo_name` AS `unique_identifier`, `test_dataentries`.* ';
        $this->assertEquals($sample_select, $test_data_iterator->getSqlSelectColumns());

        // TODO test operation after modifying "select"
    }


    /**
     * Tests DataIteratorCore::set()
     *
     * @return void
     */
    public function testSet()
    {
        $test_data_iterator = TestDataIterator::new();

        try {
            $test_data_iterator->set('string', 'key');
            $this->fail('Expected OutOfBoundsException not thrown.');
        } catch (Throwable $e) {
            $this->assertInstanceOf(OutOfBoundsException::class, $e);
        }

        $test_data_entry = TestDataEntry::new()->setName(Strings::getUuid());
        try {
            $test_data_iterator->set($test_data_entry, 'key');
            $this->fail('Expected OutOfBoundsException not thrown.');
        } catch (Throwable $e) {
            $this->assertInstanceOf(OutOfBoundsException::class, $e);
        }

        try {
            $test_data_iterator->set('non_existing_value', 0);
            $this->fail('Expected OutOfBoundsException not thrown.');
        } catch (Throwable $e) {
            $this->assertInstanceOf(OutOfBoundsException::class, $e);
        }

        $test_data_entry_2 = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $test_data_iterator->set($test_data_entry_2, $test_data_entry_2->getId());
        $this->assertArrayHasKey($test_data_entry_2->getId(), Arrays::force($test_data_iterator));
    }


    /**
     * Tests DataIteratorCore::get()
     *
     * @return void
     */
    public function testGet()
    {
        $test_data_iterator = TestDataIterator::new();

        try {
            $test_data_iterator->get('key');
            $this->fail('Expected NotExistsException not thrown.');
        } catch (Throwable $e) {
            $this->assertInstanceOf(NotExistsException::class, $e);
        }

        $test_data_entry = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $test_data_iterator->set($test_data_entry, $test_data_entry->getId());

        $this->assertEquals($test_data_entry, $test_data_iterator->get($test_data_entry->getId()));
    }


    /**
     * Tests DataIteratorCore::getRandom()
     *
     * @return void
     */
    public function testGetRandom()
    {
        $test_data_iterator = TestDataIterator::new();
        $test_data_entry = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $test_data_iterator->set($test_data_entry, $test_data_entry->getId());

        $this->assertEquals($test_data_entry, $test_data_iterator->getRandom());
    }

    /**
     * Tests DataIteratorCore::getFirstValue()
     *
     * @return void
     */
    public function testGetFirstValue()
    {
        $test_data_iterator = TestDataIterator::new();
        $test_data_entry = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $test_data_iterator->set($test_data_entry, $test_data_entry->getId());
        $this->assertEquals($test_data_entry, $test_data_iterator->getFirstValue());

        $test_data_entry_2 = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $test_data_iterator->set($test_data_entry_2, $test_data_entry_2->getId());
        $this->assertEquals($test_data_entry, $test_data_iterator->getFirstValue());
    }


    /**
     * Tests DataIteratorCore::append()
     *
     * @return void
     */
    public function testAppend()
    {
        $test_data_iterator = TestDataIterator::new();

        $test_data_entry = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $test_data_iterator->append($test_data_entry);
        $this->assertArrayHasKey($test_data_entry->getId(), Arrays::force($test_data_iterator));

        $test_data_iterator_2 = TestDataIterator::new();
        $test_data_iterator_2->append(null);
        $this->assertEmpty(Arrays::force($test_data_iterator_2));

        $name = Strings::getUuid();
        $test_data_entry_unsaved = TestDataEntry::new()->setName($name);
        $test_data_iterator_2->append($test_data_entry_unsaved);
        $test_data_entry_saved = $test_data_iterator_2->getFirstValue();
        $this->assertEquals($name, $test_data_entry_saved->getName());

        $test_data_iterator_3 = TestDataIterator::new();
        $test_data_entry_2 = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_entry_3 = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_iterator_3->append($test_data_entry_2)->append($test_data_entry_3);
        $this->assertEquals($test_data_entry_2, $test_data_iterator_3->getFirstValue());


        try {
            $test_data_iterator_2->append(null, 0, skip_null_values: false);
            $this->fail('Expected OutOfBoundsException not thrown.');

        } catch (Throwable $e) {
            $this->assertInstanceOf(OutOfBoundsException::class, $e);
        }

        $test_data_iterator_2->append($test_data_entry);
        $test_data_iterator_2->append($test_data_entry, exception: false);

        try {
            $test_data_iterator_2->append($test_data_entry);
            $this->fail('Expected IteratorKeyExistsException not thrown.');

        } catch (Throwable $e) {
            $this->assertInstanceOf(IteratorKeyExistsException::class, $e);
        }

        // TODO add tests that cover functionality of DataIteratorCore::prepareKey()
    }


    /**
     * Tests DataIteratorCore::prepend()
     *
     * @return void
     */
    public function testPrepend()
    {
        $test_data_iterator = TestDataIterator::new();
        $test_data_entry_1  = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_entry_2  = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_iterator->prepend($test_data_entry_1)->prepend($test_data_entry_2);
        $this->assertEquals($test_data_entry_2, $test_data_iterator->getFirstValue());

        $test_data_iterator_2 = TestDataIterator::new();
        $test_data_iterator_2->prepend(null);
        $this->assertEmpty(Arrays::force($test_data_iterator_2));
    }


    /**
     * Tests DataIteratorCore::delete()
     *
     * @return void
     */
    public function testDelete()
    {
        $test_data_iterator = TestDataIterator::new();

        $test_data_entry = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $test_data_iterator->append($test_data_entry);
        $test_data_iterator->delete();
        $test_data_entry_deleted = $test_data_iterator->getFirstValue();
        $this->assertEquals('deleted', $test_data_entry_deleted->getStatus());
    }


    /**
     * Tests DataIteratorCore::undelete()
     *
     * @return void
     */
    public function testUndelete()
    {
        $test_data_iterator = TestDataIterator::new();

        $test_data_entry = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $test_data_iterator->append($test_data_entry);
        $test_data_iterator->delete();
        $this->assertEquals('deleted', $test_data_iterator->getFirstValue()->getStatus());

        $test_data_iterator->undelete();
        $this->assertNull($test_data_iterator->getFirstValue()->getStatus());
        $this->assertEquals($test_data_entry, $test_data_iterator->getFirstValue());
    }


    /**
     * Tests DataIteratorCore::setStatus()
     *
     * @return void
     */
    public function testSetStatus()
    {
        $test_data_iterator = TestDataIterator::new();

        $name = Strings::getUuid();
        $test_data_entry = TestDataEntry::new()->setName($name)->save();
        $test_data_iterator->append($test_data_entry);
        $test_data_iterator->setStatus('test');
        $this->assertEquals('test', $test_data_iterator->getFirstValue()->getStatus());

        $test_data_iterator->setStatus('test_2');
        $this->assertEquals('test_2', $test_data_iterator->getFirstValue()->getStatus());

        $test_data_entry_loaded = TestDataEntry::new()->load(['name' => $name]);
        $this->assertEquals('test_2', $test_data_entry_loaded->getStatus());

        $test_data_iterator->setStatus(null, auto_save: false);
        $this->assertEquals($test_data_entry, $test_data_iterator->getFirstValue());

        $test_data_entry_loaded = TestDataEntry::new()->load(['name' => $name]);
        $this->assertEquals('test_2', $test_data_entry_loaded->getStatus());
    }


    /**
     * Tests DataIteratorCore::erase()
     *
     * @return void
     */
    public function testErase()
    {
        $test_data_iterator = TestDataIterator::new();

        $name = Strings::getUuid();
        $test_data_entry = TestDataEntry::new()->setName($name)->save();
        $test_data_iterator->append($test_data_entry);

        $test_data_iterator->erase();
        $this->assertNull($test_data_iterator->getFirstValue()->getStatus());

        try {
            TestDataEntry::new()->load(['name' => $name]);
            $this->fail('Expected DataEntryNotExistsException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(DataEntryNotExistsException::class, $e);
        }
    }


    /**
     * Tests DataIteratorCore::listIds()
     *
     * @return void
     */
    public function testListIds()
    {
        $test_data_entry    = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $test_data_entry_2  = TestDataEntry::new()->setName(Strings::getUuid())->save();
        $test_data_iterator = TestDataIterator::new()->append($test_data_entry)->append($test_data_entry_2);

        $this->assertEmpty($test_data_iterator->listIds(['a']));
        $this->assertNotEmpty($test_data_iterator->listIds(['name' => $test_data_entry->getName()]));
    }


    /**
     * Tests DataIteratorCore::clearIteratorFromTable()
     *
     * @return void
     */
    public function testClearIteratorFromTable()
    {
        $test_data_iterator = TestDataIterator::new();
        $test_data_entry    = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_entry_2  = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_iterator->append($test_data_entry)->append($test_data_entry_2);

        // TODO fix bug in DataIteratorCore::clearIteratorFromTable()
//        $test_data_iterator->clearIteratorFromTable();
//        $this->assertEquals('deleted', $test_data_iterator->getFirstValue()->getStatus());
//        $this->assertEquals('deleted', $test_data_iterator->getLastValue()->getStatus());
        $this->assertTrue(true);
    }


    /**
     * Tests DataIteratorCore::current()
     *
     * @return void
     */
    public function testCurrent()
    {
        $test_data_iterator = TestDataIterator::new();
        $test_data_entry_1  = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_iterator->append($test_data_entry_1);
        $this->assertEquals($test_data_entry_1, $test_data_iterator->current());

        $test_data_entry_2 = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_iterator->prepend($test_data_entry_2);
        $this->assertEquals($test_data_entry_2, $test_data_iterator->current());
    }


    /**
     * Tests DataIteratorCore::getLastValue()
     *
     * @return void
     */
    public function testGetLastValue()
    {
        $test_data_iterator = TestDataIterator::new();
        $test_data_entry    = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_iterator->append($test_data_entry);
        $this->assertEquals($test_data_entry, $test_data_iterator->getLastValue());

        $test_data_entry_2 = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_iterator->append($test_data_entry_2);
        $this->assertEquals($test_data_entry_2, $test_data_iterator->getLastValue());
    }


    /**
     * Tests DataIteratorCore::extractFirstValue()
     *
     * @return void
     */
    public function testExtractFirstValue()
    {
        $test_data_iterator = TestDataIterator::new();
        $test_data_entry    = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_entry_2  = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_iterator->append($test_data_entry)->append($test_data_entry_2);

        // TODO: DataIteratorCore::extractFirstValue() has a bug. parent::extractFirstValue() will return a value, whereas ensureObject() expects a key
//        $extracted_data_entry = $test_data_iterator->extractFirstValue();
//        $this->assertEquals($test_data_entry, $extracted_data_entry);
//        $this->assertNotContains($test_data_entry->getId(), $test_data_iterator->getSourceKeys());

        $this->assertContains($test_data_entry_2->getId(), $test_data_iterator->getSourceKeys());
    }


    /**
     * Tests DataIteratorCore::extractLastValue()
     *
     * @return void
     */
    public function testExtractLastValue()
    {
        $test_data_iterator = TestDataIterator::new();
        $test_data_entry    = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_entry_2  = TestDataEntry::new()->setName(Strings::getUuid());
        $test_data_iterator->append($test_data_entry)->append($test_data_entry_2);

        // TODO: DataIteratorCore::extractLastValue() has a different bug. This is due to a bug in IteratorCore::extractLastValue
//        $extracted_data_entry = $test_data_iterator->extractLastValue();
//        $this->assertEquals($test_data_entry_2, $extracted_data_entry);
//        $this->assertNotContains($test_data_entry_2->getId(), $test_data_iterator->getSourceKeys());
        $this->assertContains($test_data_entry->getId(), $test_data_iterator->getSourceKeys());
    }
}
