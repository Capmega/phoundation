<?php

/**
 * Class DataEntryTest
 *
 * This PHPUnit test class will test the \Phoundation\Data\DataEntry Object
 *
 * This PHPUnit test class will test itself against the system database table developer_unittests
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Library\Tests\Phoundation\Data\DataEntry;

use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Utils\Numbers;
use PHPUnit\Framework\TestCase;

class DataEntryTest extends TestCase
{
    /**
     * Tests DataEntry::new()
     *
     * @return void
     */
    public function testNew()
    {
        $entry = DataEntry::new();
        $this->assertInstanceOf(DataEntry::class, $entry);
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


    /**
     * Tests DataEntry::new(RANDOM_ID)
     *
     * @return void
     */
    public function testNewRandomId()
    {
        $id = Numbers::getRandomInt();

        $this->assertEquals(null, DataEntry::new($id,null,false)->getId());
    }


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


}
