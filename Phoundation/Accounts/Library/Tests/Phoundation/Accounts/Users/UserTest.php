<?php

/**
 * Class UserTest
 *
 * This PHPUnit test class will test the \Phoundation\Accounts\Users\User Object
 *
 * @author    Harrison Macey <harrison@medinet.ca>
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Library\Tests\Phoundation\Accounts\Users;

use Phoundation\Accounts\Enums\EnumAuthenticationAction;
use Phoundation\Accounts\Exception\AccountsException;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\Sessions\Exception\SessionException;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Log\Log;
use PHPUnit\Framework\TestCase;
use Throwable;

class UserTest extends TestCase
{
    /**
     * Tests User::newGuest()
     *
     * @return void
     */
    public function testNewGuest()
    {
        $guest = User::newGuest();

        $this->assertEquals('system', $guest->getStatus());
        $this->assertTrue($guest->isGuest());
        $this->assertTrue($guest->isSystemUser());
        $this->assertTrue((bool) $guest->getId());
    }


    /**
     * Tests User::save()
     *
     * @return void
     */
    public function testSave()
    {
        $unit_test_user = User::new();
        $unit_test_user->loadOrThis(['email' => 'unittest@medinet.ca'])
                       ->setFirstNames('unit')
                       ->setLastNames('test')
                       ->setNotificationsEnabled(false)
                       ->setStatus('test')->save();

        $this->assertTrue((bool) $unit_test_user->getId());
    }


    /**
     * Tests User::load()
     *
     * @return void
     */
    public function testLoad()
    {
        // Try loading system user
        $system_user = User::new()->load(['email' => 'system']);
        $this->assertEquals('system', $system_user->getStatus());

        // Try loading another user
        $unit_test_user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $this->assertTrue((bool) $unit_test_user->getId());
    }


    /**
     * Tests User::getDisplayName()
     *
     * @return void
     */
    public function testGetDisplayName()
    {
        $guest_user = User::newGuest();
        $this->assertEquals('Guest', $guest_user->getDisplayName());

        $user = User::new();
        $this->assertEquals('[NEW]', $user->getDisplayName());

        $user->setEmail('email@test.com');
        $this->assertEquals('email@test.com', $user->getDisplayName());

        $user->setFirstNames('unit')->setLastNames('test');
        $this->assertEquals('unit test', $user->getDisplayName());
        $this->assertEquals('test, unit', $user->getDisplayName(reverse: true));

        $user->setNickname('nickname');
        $this->assertEquals('nickname', $user->getDisplayName());
        $this->assertEquals('unit test', $user->getDisplayName(true));

        $user->setTitle('Mr.');
        $this->assertEquals('Mr. unit test', $user->getDisplayName(official: true, clean: true));
    }


    /**
     * Tests User::getSessionObject()
     *
     * @return void
     */
    public function testGetSessionObject()
    {
        $user = User::new()->load(['email' => 'unittest@medinet.ca']);

        try {
            $user->getSessionObject();
            $this->fail('Expected SessionException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(SessionException::class, $e);
        }
    }


    /**
     * Tests User::getRightsObject()
     *
     * @return void
     */
    public function testGetRightsObject()
    {
        $user = User::new();

        try {
            $user->getRightsObject();
            $this->fail('Expected AccountsException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(AccountsException::class, $e);
        }

        $unit_test_user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $o_rights       = $unit_test_user->getRightsObject();
        $this->assertInstanceOf(Rights::class, $o_rights);
    }


    /**
     * Tests User::getRolesObject()
     *
     * @return void
     */
    public function testGetRolesObject()
    {
        $user = User::new();

        try {
            $user->getRolesObject();
            $this->fail('Expected AccountsException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(AccountsException::class, $e);
        }

        $unit_test_user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $o_roles        = $unit_test_user->getRolesObject();
        $this->assertInstanceOf(Roles::class, $o_roles);
    }


    /**
     * Tests User::addRoles()
     *
     * @return void
     */
    public function testAddRoles()
    {
        $unit_test_user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $unit_test_user->addRoles('test');

        $o_rights = $unit_test_user->getRightsObject();
        $o_roles  = $unit_test_user->getRolesObject();

        $this->assertContains('test', $o_rights->getSourceKeys());
        $this->assertContains('test', $o_roles->getSourceKeys());
    }


    /**
     * Tests User::removeRoles()
     *
     * @return void
     */
    public function testRemoveRoles()
    {
        $unit_test_user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $unit_test_user->addRoles('test');

        $this->assertContains('test', $unit_test_user->getRightsObject()->getSourceKeys());

        $unit_test_user->removeRoles('test');
        $this->assertNotContains('test', $unit_test_user->getRolesObject()->getSourceKeys());
        $this->assertNotContains('test', $unit_test_user->getRightsObject()->getSourceKeys());
    }


    /**
     * Tests User::hasSomeRights()
     *
     * @return void
     */
    public function testHasSomeRights()
    {
        // New user should throw exception
        try {
            User::new()->hasSomeRights('everybody');
            $this->fail('Expected AccountsException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(AccountsException::class, $e);
        }

        // Test with single string
        $user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $this->assertTrue($user->hasSomeRights('everybody'));

        // Test with single entry in array
        $user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $this->assertTrue($user->hasSomeRights(['everybody']));

        // Test with one non-matching entry
        $user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $this->assertFalse($user->hasSomeRights('god'));

        // Test with one matching and one non-matching entry in array
        $user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $this->assertTrue($user->hasSomeRights(['everybody', 'god']));

        // Test with Right that doesn't exist
        $user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $this->assertFalse($user->hasSomeRights('fail'));
    }


    /**
     * Tests User::hasAllRights()
     *
     * @return void
     */
    public function testHasAllRights()
    {
        // New user should throw exception
        try {
            User::new()->hasAllRights('everybody');
            $this->fail('Expected AccountsException was not thrown');
        } catch (Throwable $e) {
            $this->assertInstanceOf(AccountsException::class, $e);
        }

        // Test with single string
        $user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $this->assertTrue($user->hasAllRights('everybody'));

        // Test with single entry in array
        $user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $this->assertTrue($user->hasAllRights(['everybody']));

        // Test with one non-matching entry
        $user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $this->assertFalse($user->hasAllRights('god'));

        // Test with one matching and one non-matching entry in array
        $user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $this->assertFalse($user->hasAllRights(['everybody', 'god']));

        // Test with Right that doesn't exist
        $user = User::new()->load(['email' => 'unittest@medinet.ca']);
        $this->assertFalse($user->hasAllRights('fail'));
    }
}
