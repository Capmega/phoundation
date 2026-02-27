<?php

namespace Phoundation\Accounts\Users\Sessions\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;

interface UserSessionsInterface extends DataIteratorInterface
{
    /**
     * Loads all active sessions into this object
     *
     * @return static
     */
    public function loadActive(): static;


    /**
     * Loads all sessions into this object
     *
     * @return static
     */
    public function loadAll(): static;


    /**
     * Loads all sessions for the specified users_id into this object
     *
     * @param int $users_id
     *
     * @return static
     */
    public function loadAllForUsersId(int $users_id): static;


    /**
     * Loads all active sessions for the specified users_id into this object
     *
     * @param int $users_id
     *
     * @return static
     */
    public function loadActiveForUsersId(int $users_id): static;


    /**
     * Loads all sessions from the specified IP address into this object
     *
     * @param string $ip
     *
     * @return static
     */
    public function loadAllForIp(string $ip): static;


    /**
     * Loads all active sessions from the specified IP address into this object
     *
     * @param string $ip
     *
     * @return static
     */
    public function loadActiveForIp(string $ip): static;


    /**
     * Returns the number of currently active sessions
     *
     * @return int
     */
    public function getActiveCount(): int;

    /**
     * Adds data to the specified sessions list
     *
     * @return static
     */
    public function addData(array $sessions_data): static;
}
