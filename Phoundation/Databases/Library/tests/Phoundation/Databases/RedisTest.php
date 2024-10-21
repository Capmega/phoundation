<?php

/**
 * \Phoundation\Databases\Redis test class
 */


declare(strict_types=1);

namespace Phoundation\Databases\Library\tests\Phoundation\Databases;

use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Exception\RedisConnectionFailedException;
use Phoundation\Databases\Exception\RedisException;
use Phoundation\Databases\Redis\Interfaces\RedisInterface;
use Phoundation\Databases\Redis\Redis;
use Phoundation\Databases\Redis\RedisQueue;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use PHPUnit\Framework\TestCase;


class RedisTest extends TestCase
{
    /**
     * @var RedisInterface
     */
    protected RedisInterface $redis;


    /**
     * Returns a (new) Redis connection
     *
     * @param bool $force
     *
     * @return RedisInterface
     */
    protected function openRedisConnection(bool $force = false): RedisInterface
    {
        if (isset($this->redis) and !$force) {
            throw new OutOfBoundsException(tr('Cannot open Redis test connection, an open connection already exists'));
        }

        return $this->redis = Redis::new(Connector::new('redis-test'))->setDatabase(0);
    }


    /**
     * Closes the Redis connection
     *
     * @return void
     */
    protected function close(): void
    {
        $this->redis->close();
        unset($this->redis);
    }


    /**
     * Test the constructor method for the Redis class
     *
     * @return void
     */
    public function testConstructor()
    {
        $redis = $this->openRedisConnection();

//        $this->expectNotToPerformAssertions(); //expect no exception when constructing properly
        $this->assertEquals(0, $redis->getDatabase());
        $this->close();
    }


    /**
     * Test the constructor method for the Redis class
     *
     * @return void
     */
    public function testClose()
    {
        $this->expectNotToPerformAssertions(); //expect no exception when constructing properly
//        $this->openRedisConnection()->close();
        $this->openRedisConnection();
        $this->close();
    }


    /**
     * Test the constructor method for the Redis class
     *
     * @return void
     */
    public function testPing()
    {
        $this->redis = Redis::new(Connector::new('redis-test'))->setDatabase(0);

        $this->assertTrue($this->redis->ping(), 'Pinging connection (from connector object) should return true');

//        $this->assertNull(Redis::new(connector::new('invalid'))->ping());

        $this->openRedisConnection(true);
        $this->assertTrue($this->redis->ping(), 'Pinging connection should still return true');
        $this->redis->close();

        $this->redis = Redis::new('test');
        $this->assertTrue($this->redis->ping(), 'Pinging connection (from string) should return true');
        $this->redis->close();

        $this->expectException(RedisException::class);
        $this->redis = Redis::new(Connector::new('new-connector'), false);

        $this->redis->close();
    }


    /**
     * ???
     * 
     * @return void
     */
    public function testCreateList()
    {
        $this->redis = Redis::new(Connector::new('redis-test'))->setDatabase(0);
        
        $this->redis->set(null, 'key1');
        $this->assertNull($this->redis->get('key1'), '"Get" on key1 should return null');
        
        $this->redis->set(['value1','value2'], 'key2');
        $this->assertEquals(['value1','value2'],$this->redis->get('key2'), '"get" on key2 should return values');
        
        $this->redis->delValue('key2');
        $this->assertNull($this->redis->get('key2'), '"get" on key2 should return null');

        $this->assertNull($this->redis->get('key3'), '"get" on key3 should return null');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Test flushing (clearing) a whole database
     *
     * @return void
     */
    public function testFlush()
    {
        $this->redis = Redis::new(Connector::new('redis-test'))->setDatabase(0);

        $this->redis->set('value', 'key1');
        $this->redis->clearAll();
        $this->assertNull($this->redis->get('key1'), '"get" on key2 should return null');

        $this->redis->clearAll();

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Test pushing a value to a queue
     *
     * @return void
     */
    public function testPush()
    {
        $this->redis = Redis::new(Connector::new('redis-test'))->setDatabase(0);

        $value1 = 'test-value-1';
        $this->redis->push($value1, 'test-queue');

        $this->assertContains($value1, $this->redis->getQueue('test-queue'), 'The pushed value should be in the queue.');
        $this->assertEquals(1, $this->redis->getQueueLength('test-queue'), 'The queue count should be 1 after the first push.');

        $value2 = 'test-value-2';
        $this->redis->push($value2, 'test-queue');

        $this->assertEquals(2, $this->redis->getQueueLength('test-queue'), 'The queue count should be 2 after both pushes.');
        $this->assertEquals($value1, $this->redis->queuePeek('test-queue'), 'The first value in the queue should be the pushed value.');

        $this->redis->push(null, 'test-queue');
        $this->assertEquals(2, $this->redis->getQueueLength('test-queue'), 'The queue count should still be 2 after pushing null.');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Test popping a value from a queue
     *
     * @return void
     */
    public function testPop()
    {
        $this->redis = Redis::new(Connector::new('redis-test'))->setDatabase(0);
        $this->redis->clearAll();

        $this->redis->push('test-value-1', 'test-queue');
        $this->redis->push('test-value-2', 'test-queue');
        $poppedValue1 = $this->redis->pop('test-queue');

        $this->assertEquals('test-value-1', $poppedValue1, 'The popped value should be the last value pushed.');
        $this->assertEquals(1, $this->redis->getQueueLength('test-queue'), 'The queue count should decrease by 1 after a pop operation.');
        $this->assertNotContains('test-value-1', $this->redis->getQueue('test-queue'), 'The popped value should no longer exist in the queue.');

        $this->assertEquals('test-value-2', $this->redis->pop('test-queue'), 'The next popped value should be the first value pushed.');

        $this->assertNull($this->redis->pop('test-queue'), 'Popping empty queue should throw Exception');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Test getQueue method
     *
     * @return void
     */
    public function testGetQueue()
    {
        $this->redis = Redis::new(Connector::new('redis-test'))->setDatabase(0);

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
     * tests queueExists method
     *
     * @return void
     */
    public function testQueueExists()
    {
        $this->redis = Redis::new(Connector::new('redis-test'))->setDatabase(0);

        $this->redis->push('test-value-1', 'test-queue');
        $this->assertTrue($this->redis->queueExists('test-queue'), 'This queue should exist');
        $this->assertFalse($this->redis->queueExists('fake-queue'), 'This queue should not exist');

        $this->redis->set("value1", "key1");
        $this->assertFalse($this->redis->queueExists("key1"), 'This is a key, not a queue');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Tests queuePeek method
     *
     * @return void
     */
    public function testPeek()
    {
        $this->redis = Redis::new(Connector::new('redis-test'))->setDatabase(0);
        
        $this->redis->push('test-value', 'test-queue');
        $result = $this->redis->queuePeek('test-queue');

        $this->assertEquals('test-value', $result, 'Peek should return the first value without removing it.');
        $this->assertEquals(1, $this->redis->getQueueLength('test-queue'), 'The queue count should still be 1.');

        $this->assertNull($this->redis->queuePeek('test-queue', 10), 'Return null if out of bounds');

        $this->redis->clearAll();

        $this->assertNull($this->redis->queuePeek('test-queue'),'Peek should return null on an empty list');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Tests the getQueueLength method
     *
     * @return void
     */
    public function testGetCount()
    {
        $this->redis = Redis::new(Connector::new('redis-test'))->setDatabase(0);
        $this->redis->clearAll();

        $result1 = $this->redis->getQueueLength('test-queue');
        $this->assertEquals(0, $result1, 'The queue should be empty at first.');

        $this->redis->push('test-value', 'test-queue');
        $result2 = $this->redis->getQueueLength('test-queue');
        $this->assertEquals(1, $result2, 'The queue should have size 1 after push');

        $this->redis->pop('test-queue');
        $result3 = $this->redis->getQueueLength('test-queue');
        $this->assertEquals(0, $result3, 'The queue should be empty after pop.');

        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Tests getQueueLength method
     *
     * @return void
     */
    public function testClearQueue()
    {
        $this->redis = Redis::new(Connector::new('redis-test'))->setDatabase(0);
        $this->redis->clearall();

        $this->redis->push('value1','test-queue');
        $result1 = $this->redis->getQueueLength('test-queue');
        $this->assertEquals(1, $result1, 'The queue should have size 1 after push');

        $this->redis->clearQueue('test-queue');
        $result2 = $this->redis->getQueueLength('test-queue');
        $this->assertEquals(0, $result2, 'The queue should be empty after clear.');

        $this->redis->clearQueue('test-queue');
        $result3 = $this->redis->getQueueLength('test-queue');
        $this->assertEquals(0, $result3, 'The queue should still be empty after clear, no errors.');

        $this->expectException(RedisException::class); //Non-existent queue should throw exception.
        $this->redis->clearQueue('test-queue-2');


        $this->redis->clearAll();
        $this->redis->close();
    }


    /**
     * Tests showAll() method
     *
     * @return void
     */
    public function testShowAll()
    {
        $this->redis = Redis::new(Connector::new('redis-test'))->setDatabase(0);

        $this->redis->push('value1','test-queue');
        $this->redis->push('value-2', 'test-queue');
        $this->redis->push('value-3', 'test-queue-2');
        $this->redis->push('value-4', 'test-queue-3');

        $result = array();
        array_push($result, 'queue_test-queue-3','queue_test-queue-2', 'queue_test-queue');

        $this->assertEquals($result, $this->redis->showAll(), 'The result array should equal the sample array');
        $this->redis->clearAll();
        $this->redis->close();
    }


}
