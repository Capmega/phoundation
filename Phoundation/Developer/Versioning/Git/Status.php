<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Developer\Versioning\Git\Exception\GitUnknownStatusException;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusInterface;

/**
 * Class Status
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */
class Status implements StatusInterface
{
    /**
     * The specified status
     *
     * @var string $status
     */
    protected string $status;

    /**
     * True if this file is new
     *
     * @var bool $flag_new
     */
    protected bool $flag_new = false;

    /**
     * True if this file was modified
     *
     * @var bool $flag_modified
     */
    protected bool $flag_modified = false;

    /**
     * True if this file was renamed
     *
     * @var bool $flag_renamed
     */
    protected bool $flag_renamed = false;

    /**
     * True if the change is in the index
     *
     * @var bool $flag_indexed
     */
    protected bool $flag_indexed = false;

    /**
     * If true, this file is tracked by git. If false, it's not
     *
     * @var bool $flag_tracked
     */
    protected bool $flag_tracked = true;

    /**
     * If true, then this file was deleted
     *
     * @var bool $flag_deleted
     */
    protected bool $flag_deleted = false;

    /**
     * If true, then this file has a git conflict
     *
     * @var bool $is_conflict
     */
    protected bool $is_conflict = false;

    /**
     * The human-readable explanation for the current status
     *
     * @var string $flag_readable
     */
    protected string $readable = 'Unknown';


    /**
     * Status class constructor
     *
     * @param string $status
     */
    public function __construct(string $status)
    {
        $this->parseStatus($status);
    }


    /**
     * Parses the specified git status string
     *
     * @param string $status
     *
     * @return void
     */
    protected function parseStatus(string $status): void
    {
        $this->status = $status;
        switch ($status) {
            case 'D ':
                $this->flag_deleted = true;
                $this->readable     = tr('Deleted indexed');
                break;
            case ' D':
                $this->flag_deleted = true;
                $this->readable     = tr('Deleted');
                break;
            case 'AD':
                $this->flag_deleted = true;
                $this->flag_indexed = true;
                $this->readable     = tr('New file indexed but deleted');
                break;
            case 'AM':
                $this->flag_new      = true;
                $this->flag_modified = true;
                $this->readable      = tr('New file indexed and modified');
                break;
            case 'A ':
                $this->flag_new      = true;
                $this->flag_modified = true;
                $this->readable      = tr('New file indexed');
                break;
            case ' M':
                $this->flag_modified = true;
                $this->readable      = tr('Modified');
                break;
            case 'M ':
                $this->flag_indexed  = true;
                $this->flag_modified = true;
                $this->readable      = tr('Modified and indexed');
                break;
            case 'MM':
                $this->flag_indexed  = true;
                $this->flag_modified = true;
                $this->readable      = tr('Modified and indexed and modified');
                break;
            case 'R ':
                $this->flag_renamed  = true;
                $this->flag_modified = true;
                $this->readable      = tr('Renamed indexed');
                break;
            case 'RM':
                $this->flag_renamed  = true;
                $this->flag_modified = true;
                $this->readable      = tr('Renamed indexed and modified');
                break;
            case 'T ':
                $this->flag_modified = true;
                $this->readable      = tr('Type changed');
                break;
            case '??':
                $this->flag_tracked = false;
                $this->readable     = tr('Not tracked');
                break;
            case '  ':
                $this->readable = tr('No changes');
                break;
            case 'both modified':
                $this->readable      = tr('Both modified');
                $this->is_conflict   = true;
                $this->flag_modified = true;
                break;
            case 'UD':
                $this->readable      = tr('Deleted by them');
                $this->is_conflict   = true;
                $this->flag_modified = true;
                break;
            case 'DU':
                $this->readable      = tr('Deleted by us');
                $this->is_conflict   = true;
                $this->flag_modified = true;
                break;
            default:
                throw new GitUnknownStatusException(tr('Unknown git status ":status" specified', [
                    ':status' => $status,
                ]));
        }
    }


    /**
     * Returns a new Status object
     *
     * @param string $status
     *
     * @return static
     */
    public function new(string $status): static
    {
        return new Status($status);
    }


    /**
     * Returns the status as a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->status;
    }


    /**
     * Returns the status string
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }


    /**
     * Returns a readable status string
     *
     * @return string
     */
    public function getReadable(): string
    {
        return $this->readable;
    }


    /**
     * Returns if this file is new or not
     *
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->flag_new;
    }


    /**
     * Returns if this file is modified or not
     *
     * @return bool
     */
    public function isModified(): bool
    {
        return $this->flag_modified;
    }


    /**
     * Returns if this file is indexed or not
     *
     * @return bool
     */
    public function isIndexed(): bool
    {
        return $this->flag_indexed;
    }


    /**
     * Returns if this file is deleted or not
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->flag_deleted;
    }


    /**
     * Returns if this file is renamed or not
     *
     * @return bool
     */
    public function isRenamed(): bool
    {
        return $this->flag_renamed;
    }


    /**
     * Returns true if this status is a conflict
     *
     * @return bool
     */
    public function isConflict(): bool
    {
        return $this->is_conflict;
    }


    /**
     * Returns if this file is tracked or not
     *
     * @return bool
     */
    public function isTracked(): bool
    {
        return $this->flag_tracked;
    }
}
