<?php

/**
 * Activities class
 *
 * This Core library HTML widget component object can render the HTML required to display multiple metadata activities
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Core\Meta\Activities;

use PDOStatement;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\Interfaces\ArraySourceInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\IteratorCore;
use Phoundation\Data\Traits\TraitMethodHasRendered;
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;

class Activities extends IteratorCore
{
    use TraitMethodHasRendered;


    /**
     * Tracks if read activities should be hidden
     *
     * @var bool $hide_reads
     */
    protected bool $hide_reads = false;


    /**
     * Activities class constructor
     */
    public function __construct()
    {
        $this->setAcceptedDataTypes(Activity::class);
    }


    /**
     * Returns a new activities object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }


    /**
     * Returns true if the reads will be hidden
     *
     * @return bool
     */
    public function getHideReads(): bool
    {
        return $this->hide_reads;
    }


    /**
     * Returns true if the reads will be hidden
     *
     * @param bool $hide_reads
     *
     * @return Activities
     */
    public function setHideReads(bool $hide_reads): static
    {
        $this->hide_reads = $hide_reads;

        return $this;
    }


    /**
     * Returns a new activities object
     *
     * @param ArraySourceInterface|array|string|null $source
     *
     * @return Activities|null
     */
    public static function newFromSourceOrNull(ArraySourceInterface|array|string|null $source): ?static
    {
        if ($source === null) {
            return null;
        }

        return static::new()->setSource($source);
    }


    /**
     * Returns a new activities object
     *
     * @param DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                                          $execute
     *
     * @return static
     */
    public static function newFromSource(DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source = null, ?array $execute = null): static
    {
        return static::new()->setSource($source, $execute);
    }


    /**
     * Loads activities for the specified user within the specified date ranges
     *
     * @param UserInterface        $user
     * @param PhoDateTimeInterface $start
     * @param PhoDateTimeInterface $stop
     *
     * @return static
     */
    public function loadForUserDateRange(UserInterface $user, PhoDateTimeInterface $start, PhoDateTimeInterface $stop): static
    {
        $this->source = sql()->listKeyValues('SELECT `id`
                                                           `created_on`,
                                                           `meta_id`,
                                                           `action`,
                                                           `source`,
                                                           `comments`,
                                                           `data`
                
                                                    FROM   `meta_history`
                                                    
                                                    WHERE  `users_id`    = :users_id
                                                      AND  `created_on` >= :start
                                                      AND  `created_on` <= :stop', [
                                                          ':users_id' => $user->getId(),
                                                          ':start'    => $start->format(EnumDateFormat::mysql_datetime),
                                                          ':stop'     => $start->format(EnumDateFormat::mysql_datetime)
                        ]);

        return $this;
    }


    /**
     * Loads activities for the specified meta id
     *
     * @param DataEntryInterface|int    $meta_id
     * @param PhoDateTimeInterface|null $start
     * @param PhoDateTimeInterface|null $stop
     *
     * @return static
     */
    public function loadForMetaId(DataEntryInterface|int $meta_id, ?PhoDateTimeInterface $start = null, ?PhoDateTimeInterface $stop = null): static
    {
        if ($meta_id instanceof DataEntryInterface) {
            $object  = $meta_id;
            $meta_id = $object->getMetaId();

            if (empty($meta_id)) {
                throw new OutOfBoundsException(tr('Specified DataEntry object ":class" has no meta data associated with it', [
                    ':class' => get_class($object)
                ]));
            }

        } else {
            $object = null;

            if (!$meta_id or ($meta_id < 1)) {
                throw new OutOfBoundsException(tr('Invalid meta_id ":meta_id" specified, it must be a valid database id', [
                    ':meta_id' => $meta_id
                ]));
            }

        }

        $execute = [
            ':meta_id' => $meta_id
        ];

        if ($start) {
            $execute[':start'] = $start->format(EnumDateFormat::mysql_datetime);
        }

        if ($stop) {
            $execute[':stop'] = $stop->format(EnumDateFormat::mysql_datetime);
        }

        $this->source = sql()->listKeyValues('SELECT   `id`,
                                                       `created_on`,
                                                       `created_by`,
                                                       `action`,
                                                       `source`,
                                                       `comments`,
                                                       `data`
            
                                              FROM     `meta_history`
                                                
                                              WHERE    `meta_id` = :meta_id
                                              ORDER BY `created_on` DESC' .
                                    ($start ? ' AND `start` >= :start' : null) .
                                     ($stop ? ' AND `stop`  >= :stop'  : null), $execute);

        $this->checkEmpty($meta_id, $object);

        return $this;
    }


    /**
     * Checks if the specified meta_id exists if no history has come up
     *
     * @param int                     $meta_id
     * @param DataEntryInterface|null $object
     *
     * @return void
     */
    protected function checkEmpty(int $meta_id, ?DataEntryInterface $object): void
    {
        if (empty($this->source)) {
            $exists = sql()->getRow('SELECT `id` 
                                     FROM   `meta` 
                                     WHERE  `id` = :id', [
                ':id' => $meta_id
            ]);

            if (!$exists) {
                if (empty($object)) {
                    throw new NotExistsException(tr('The specified meta_id ":meta_id" does not exist', [
                        ':meta_id' => $meta_id
                    ]));
                }

                throw new NotExistsException(tr('The ":class" class object has meta_id ":meta_id" which does not exist', [
                    ':class'   => get_class($object),
                    ':meta_id' => $meta_id
                ]));
            }
        }
    }


    /**
     * Renders and returns the HTML to display this meta activity
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (empty($this->render)) {
            foreach ($this as $o_activity) {
                if (!$this->hide_reads or !$o_activity->isAction('read')) {
                    $this->render .= $o_activity->render();
                }
            }
        }

        return $this->render;
   }
}
