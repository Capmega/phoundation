<?php

/**
 * Class RedisTest
 *
 * This PHPUnit test class will test the \Phoundation\Databases\Redis Object
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Library\tests\Phoundation\Databases;

use Phoundation\Core\Log\Log;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Redis\Interfaces\RedisInterface;
use Phoundation\Databases\Redis\Redis;
use PHPUnit\Framework\TestCase;

class RedisTest extends TestCase
{
    /**
     * @var RedisInterface
     */
    protected RedisInterface $redis;


    /**
     * Returns Redis::new()
     *
     * @param bool $force
     *
     * @return void
     */
    protected function ensureRedisConnectionOpen(bool $force = false): void
    {
        if (isset($this->redis) and !$force) {
            return;
        }

        $o_connector = Connector::new('redis-queue');
        $this->redis = Redis::new($o_connector)->setDatabase(0, true);

        if (!$this->redis->ping()) {
            Log::error(tr('Connection to Redis server failed'));
        }

    }


    /**
     * Closes Redis::close()
     *
     * @return void
     */
    protected function close(): void
    {
        $this->redis->close();
        unset($this->redis);
    }


    /**
     * Test Redis::__construct()
     *
     * @return void
     */
    public function testConstructor()
    {
        $this->ensureRedisConnectionOpen();
        $this->assertEquals(0, $this->redis->getDatabase());

        $this->close();
    }


    /**
     * Test Redis::close()
     *
     * @return void
     */
    public function testClose()
    {
        $this->expectNotToPerformAssertions();
        $this->ensureRedisConnectionOpen();
        $this->close();
    }


    /**
     * Test Redis::ping()
     *
     * @return void
     */
    public function testPing()
    {
        $this->ensureRedisConnectionOpen();

        $this->assertTrue($this->redis->ping(), 'Pinging connection (from connector object) should return true');

        $this->ensureRedisConnectionOpen(true);
        $this->assertTrue($this->redis->ping(), 'Pinging connection should still return true');
        $this->redis->close();

        $this->redis = Redis::new('test');
        $this->assertTrue($this->redis->ping(), 'Pinging connection (from string) should return true');

        $this->redis->close();
    }


    /**
     * test Redis::set()
     *
     * @return void
     */
    public function testCreateList()
    {
        $this->ensureRedisConnectionOpen();

        $this->redis->set(null, 'key1');
        $this->assertNull($this->redis->get('key1'), '"Get" on key1 should return null');

        $this->redis->set(['value1','value2'], 'key2');
        $this->assertEquals(['value1','value2'],$this->redis->get('key2'), '"get" on key2 should return values');

        $this->redis->deleteValue('key2');
        $this->assertNull($this->redis->get('key2'), '"get" on key2 should return null');

        $this->assertNull($this->redis->get('key3'), '"get" on key3 should return null');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Test Redis::flush()
     *
     * @return void
     */
    public function testFlush()
    {
        $this->ensureRedisConnectionOpen();

        $this->redis->set('value', 'key1');
        $this->redis->clearAll();
        $this->assertNull($this->redis->get('key1'), '"get" on key2 should return null');

        $this->redis->clearAll();

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Test Redis::push()
     *
     * @return void
     */
    public function testPush()
    {
        $this->ensureRedisConnectionOpen();

        $value1 = 'test-value-1';
        $this->redis->push($value1, 'test-queue');

        $this->assertContains($value1, $this->redis->getQueue('test-queue'), 'The pushed value should be in the queue.');
        $this->assertEquals(1, $this->redis->getQueueCount('test-queue'), 'The queue count should be 1 after the first push.');

        $value2 = 'test-value-2';
        $this->redis->push($value2, 'test-queue');

        $this->assertEquals(2, $this->redis->getQueueCount('test-queue'), 'The queue count should be 2 after both pushes.');
        $this->assertEquals($value1, $this->redis->queuePeek('test-queue'), 'The first value in the queue should be the pushed value.');

        $this->redis->push(null, 'test-queue');
        $this->assertEquals(2, $this->redis->getQueueCount('test-queue'), 'The queue count should still be 2 after pushing null.');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Test Redis::pop()
     *
     * @return void
     */
    public function testPop()
    {
        $this->ensureRedisConnectionOpen();
        $this->redis->clearAll();

        $this->redis->push('test-value-1', 'test-queue');
        $this->redis->push('test-value-2', 'test-queue');
        $poppedValue1 = $this->redis->pop('test-queue');

        $this->assertEquals('test-value-1', $poppedValue1, 'The popped value should be the last value pushed.');
        $this->assertEquals(1, $this->redis->getQueueCount('test-queue'), 'The queue count should decrease by 1 after a pop operation.');
        $this->assertNotContains('test-value-1', $this->redis->getQueue('test-queue'), 'The popped value should no longer exist in the queue.');

        $this->assertEquals('test-value-2', $this->redis->pop('test-queue'), 'The next popped value should be the first value pushed.');

        $this->assertNull($this->redis->pop('test-queue'), 'Popping empty queue should throw Exception');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Test Redis::getQueue()
     *
     * @return void
     */
    public function testGetQueue()
    {
        $this->ensureRedisConnectionOpen();

        $this->redis->push('test-value-1', 'test-queue');
        $this->redis->push('test-value-2', 'test-queue');

        $result1 = $this->redis->getQueue('test-queue',0, -1);
        $this->assertEquals(['test-value-1', 'test-value-2'], $result1, 'The queue should contain both values.');

        $result2 = $this->redis->getQueue('test-queue',1, 1);
        $this->assertEquals(['test-value-2'], $result2, 'This selection should include only one value.');

        $result3 = $this->redis->getQueue('test-queue',1, 0);
        $this->assertEquals([], $result3, 'This selection should include no values');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Tests Redis::queueExists()
     *
     * @return void
     */
    public function testQueueExists()
    {
        $this->ensureRedisConnectionOpen();

        $this->redis->push('test-value-1', 'test-queue');
        $this->assertTrue($this->redis->queueExists('test-queue'), 'This queue should exist');
        $this->assertFalse($this->redis->queueExists('fake-queue'), 'This queue should not exist');

        $this->redis->set("value1", "key1");
        $this->assertFalse($this->redis->queueExists("key1"), 'This is a key, not a queue');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Tests Redis::queuePeek()
     *
     * @return void
     * @todo Rename this method, should be static::testQueuePeek()?
     */
    public function testPeek()
    {
        $this->ensureRedisConnectionOpen();

        $this->redis->push('test-value', 'test-queue');
        $result = $this->redis->queuePeek('test-queue');

        $this->assertEquals('test-value', $result, 'Peek should return the first value without removing it.');
        $this->assertEquals(1, $this->redis->getQueueCount('test-queue'), 'The queue count should still be 1.');

        $this->assertNull($this->redis->queuePeek('test-queue', 10), 'Return null if out of bounds');

        $this->redis->clearAll();

        $this->assertNull($this->redis->queuePeek('test-queue'),'Peek should return null on an empty list');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Tests Redis::getQueueLength()
     *
     * @todo Rename this method, should be static::testGetQueueLength()?  Should there be a static::testGetCount() too?
     * @return void
     */
    public function testGetQueueCount()
    {
        $this->ensureRedisConnectionOpen();
        $this->redis->clearAll();

        $result1 = $this->redis->getQueueCount('test-queue');
        $this->assertEquals(0, $result1, 'The queue should be empty at first.');

        $this->redis->push('test-value', 'test-queue');
        $result2 = $this->redis->getQueueCount('test-queue');
        $this->assertEquals(1, $result2, 'The queue should have size 1 after push');

        $this->redis->pop('test-queue');
        $result3 = $this->redis->getQueueCount('test-queue');
        $this->assertEquals(0, $result3, 'The queue should be empty after pop.');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Tests Redis::getQueueLength()
     *
     * @return void
     */
    public function testClearQueue()
    {
        $this->ensureRedisConnectionOpen();
        $this->redis->clearall();

        $this->redis->push('value1','test-queue');
        $result1 = $this->redis->getQueueCount('test-queue');
        $this->assertEquals(1, $result1, 'The queue should have size 1 after push');

        $this->redis->clearQueue('test-queue');
        $result2 = $this->redis->getQueueCount('test-queue');
        $this->assertEquals(0, $result2, 'The queue should be empty after clear.');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Tests Redis::showAll()
     *
     * @return void
     */
    public function testShowAll()
    {
        $this->ensureRedisConnectionOpen();
        $this->redis->clearAll();

        $this->redis->push('value-1','test-queue');
        $this->redis->push('value-2', 'test-queue');
        $this->redis->push('value-3', 'test-queue-2');
        $this->redis->push('value-4', 'test-queue-3');

        $result = [];
        array_push($result, 'queue_test-queue-3','queue_test-queue', 'queue_test-queue-2');

        $this->assertEqualsCanonicalizing($result, $this->redis->getAllKeys(), 'The result array should equal the sample array');
        $this->redis->clearAll();
        $this->redis->close();
    }
}
