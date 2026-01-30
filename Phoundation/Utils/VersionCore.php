<?php

/**
 * Class VersionCore
 *
 * This core class can contain and manage MAJOR.MINOR.REVISION type versions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils;

use Phoundation\Data\Traits\TraitDataStringSource;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Validate;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Enums\EnumVersionSections;
use Phoundation\Utils\Exception\VersionCannotBeModifiedException;
use Phoundation\Utils\Interfaces\VersionInterface;


class VersionCore implements VersionInterface
{
    use TraitDataStringSource {
        setSource as protected __setSource;
    }


    /**
     * Tracks if this version is a short version or not
     *
     * @var bool $short_version
     */
    protected bool $short_version = false;


    /**
     * Returns true if this version is a short version
     *
     * @return bool
     */
    public function getShortVersion(): bool
    {
        return $this->short_version;
    }


    /**
     * Sets the source for this Version object
     *
     * @param string|int|null $source                The source for this Version object
     * @param bool            $short_version [false] If true will work with short versions (8.4) instead of long versions (8.4.3)
     *
     * @return static
     */
    public function setSource(string|int|null $source, bool $short_version = false): static
    {
        $this->short_version = $short_version;

        if ($source !== null) {
            if (is_int($source)) {
                $source = VersionCore::convertIntegerToString($source);
            }

            // Make sure we have a valid version!
            Validate::new($source)->isVersion(short_version: $short_version);
        }

        return $this->__setSource($source);
    }


    /**
     * Returns the major component of this version
     *
     * @return int
     */
    public function getMajor(): int
    {
        return $this->getSourceAsArray()[0];
    }


    /**
     * Returns the minor component of this version
     *
     * @return int
     */
    public function getMinor(): int
    {
        return $this->getSourceAsArray()[0];
    }


    /**
     * Returns the revision component of this version
     *
     * @return int
     */
    public function getRevision(): int
    {
        return $this->getSourceAsArray()[0];
    }


    /**
     * Increases the specified version section by the specified amount
     *
     * @param EnumVersionSections|string $section      The section to increase
     * @param int                        $by_value [1] The amount to increase the version section by
     *
     * @return static
     */
    public function increaseSection(EnumVersionSections|string $section, int $by_value = 1): static
    {
        if (is_string($section)) {
            $section = EnumVersionSections::from($section);
        }

        switch ($section) {
            case EnumVersionSections::major:
                return $this->increaseMajor($by_value);

            case EnumVersionSections::minor:
                return $this->increaseMinor($by_value);

            case EnumVersionSections::revision:
                return $this->increaseRevision($by_value);
        }

        throw new OutOfBoundsException(ts('Invalid section ":section" specified', ['section' => $section]));
    }


    /**
     * Decreases the specified version section by the specified amount
     *
     * @param EnumVersionSections|string $section      The section to increase
     * @param int                        $by_value [1] The amount to increase the version section by
     *
     * @return static
     */
    public function decreaseSection(EnumVersionSections|string $section, int $by_value = 1): static
    {
        if (is_string($section)) {
            $section = EnumVersionSections::from($section);
        }

        switch ($section) {
            case EnumVersionSections::major:
                return $this->increaseMajor($by_value);

            case EnumVersionSections::minor:
                return $this->increaseMinor($by_value);

            case EnumVersionSections::revision:
                return $this->increaseRevision($by_value);
        }

        throw new OutOfBoundsException(ts('Invalid section ":section" specified', ['section' => $section]));
    }


    /**
     * Increases the major version by the specified amount
     *
     * @param int $by_value [1] The amount to increase the major version by
     *
     * @return static
     */
    public function increaseMajor(int $by_value = 1): static
    {
        return $this->increaseSectionDirectly($by_value, 0);
    }


    /**
     * Increases the minor version by the specified amount
     *
     * @param int $by_value [1] The amount to increase the minor version by
     *
     * @return static
     */
    public function increaseMinor(int $by_value = 1): static
    {
        return $this->increaseSectionDirectly($by_value, 1);
    }


    /**
     * Increases the revision version by the specified amount
     *
     * @param int $by_value [1] The amount to increase the revision version by
     *
     * @return static
     */
    public function increaseRevision(int $by_value = 1): static
    {
        return $this->increaseSectionDirectly($by_value, 2);
    }


    /**
     * Decreases the major version by the specified amount
     *
     * @param int $by_value [1] The amount to decrease the major version by
     *
     * @return static
     */
    public function decreaseMajor(int $by_value = 1): static
    {
        return $this->decreaseSectionDirectly($by_value, 0);
    }


    /**
     * Decreases the minor version by the specified amount
     *
     * @param int $by_value [1] The amount to decrease the minor version by
     *
     * @return static
     */
    public function decreaseMinor(int $by_value = 1): static
    {
        return $this->decreaseSectionDirectly($by_value, 1);
    }


    /**
     * Decreases the revision version by the specified amount
     *
     * @param int $by_value [1] The amount to decrease the revision version by
     *
     * @return static
     */
    public function decreaseRevision(int $by_value = 1): static
    {
        return $this->decreaseSectionDirectly($by_value, 2);
    }


    /**
     * Increases the specified section version by the specified amount
     *
     * @param int $by_value The amount to increase the section version by
     * @param int $section  The section for which to increase the value
     *
     * @return static
     */
    protected function increaseSectionDirectly(int $by_value, int $section): static
    {
        $this->checkCanModify()->checkModifier($by_value);

        $source            = $this->getSourceAsArray();
        $source[$section] += $by_value;

        return $this->checkValue($source[$section])
                    ->setSourceAsArray($source);
    }


    /**
     * Decreases the specified section version by the specified amount
     *
     * @param int $by_value The amount to decrease the section version by
     * @param int $section  The section for which to decrease the value
     *
     * @return static
     */
    protected function decreaseSectionDirectly(int $by_value, int $section): static
    {
        $this->checkCanModify()->checkModifier($by_value);

        $source            = $this->getSourceAsArray();
        $source[$section] -= $by_value;

        return $this->checkValue($source[$section])
                    ->setSourceAsArray($source);
    }


    /**
     * Returns true if this version can be modified
     *
     * @return bool
     */
    protected function canModify(): bool
    {
        return match ($this->source) {
            'post_once', 'post_always' => false,
            default                    => true,
        };
    }


    /**
     * Throws a OutOfBoundsException if the version modifier is invalid
     *
     * The version modifier value must be between 1 and 999
     *
     * @param int $value The version modifier value to test
     *
     * @return static
     */
    protected function checkModifier(int $value): static
    {
        if (($value < 1) or ($value > 999)) {
            throw new OutOfBoundsException(ts('Specified version modifier value ":version" is invalid, it must be an integer between 1 and 999.', [
                ':version' => $value
            ]));
        }

        return $this;
    }


    /**
     * Throws a OutOfBoundsException if the version value is invalid
     *
     * The version value must be between 1 and 999
     *
     * @param int $value The version value to test
     *
     * @return static
     */
    protected function checkValue(int $value): static
    {
        if (($value < 1) or ($value > 999)) {
            throw new OutOfBoundsException(ts('Version section value ":version" is invalid, it must be an integer between 1 and 999', [
                ':version' => $value
            ]));
        }

        return $this;
    }


    /**
     * Throws a VersionCannotBeModifiedException if the version cannot be modified
     *
     * Typically, this would be only for 'post_once', and 'post_always'
     *
     * @return static
     * @throws VersionCannotBeModifiedException
     */
    protected function checkCanModify(): static
    {
        if ($this->canModify()) {
            return $this;
        }

        throw new VersionCannotBeModifiedException(ts('Cannot modify the version ":version"', [
            ':version' => $this->source
        ]));
    }


    /**
     * Converts the specified integer version to a string version
     *
     * @param int $version The integer version to convert to a string
     *
     * @return string
     */
    protected static function convertIntegerToString(int $version): string
    {
        if ($version < 0) {
            return match ($version) {
                -1      => 'post_once',
                -2      => 'post_always',
                default => throw new OutOfBoundsException(tr('Invalid version string ":version" specified', [
                    ':version' => $version,
                ]))
            };
        }

        $major    = floor($version / 1000000);
        $minor    = floor(($version - ($major * 1000000)) / 1000);
        $revision = fmod($version, 1000);

        if ($major > 999) {
            throw new OutOfBoundsException(tr('The major of version ":version" cannot be greater than "999"', [
                ':version' => $version,
            ]));
        }

        if ($minor > 999) {
            throw new OutOfBoundsException(tr('The minor of version ":version" cannot be greater than "999"', [
                ':version' => $version,
            ]));
        }

        if ($revision > 999) {
            throw new OutOfBoundsException(tr('The revision of version ":version" cannot be greater than "999"', [
                ':version' => $version,
            ]));
        }

        return $major . '.' . $minor . '.' . $revision;
    }


    /**
     * Validates the specified source array
     *
     * @param array $source The version array to validate
     *
     * @return static
     */
    protected function validateVersionArray(array $source): static
    {
        if (count($source) !== 3) {
            throw ValidationFailedException::new(ts('The specified version array is invalid, it must have exactly 3 elements'))
                                           ->addData(['version' => $source]);
        }

        // Make sure the version keys are valid. Either must be 0, 1, 2 or major, minor, revision
        $numerical = null;
        $keys      = [
            0 => 'major',
            1 => 'minor',
            2 => 'revision',
        ];

        foreach ($keys as $id => $label) {
            if (array_key_exists($id, $source)) {
                if ($numerical === false) {
                    throw ValidationFailedException::new(ts('The specified version array is invalid, it must be a numerical array with element keys "0 => value, 1 => value, 2 => value" or "major => value, minor => value, revision => value", these keys cannot be mixed'))
                                                   ->addData(['version' => $source]);
                }

                $numerical = true;
                continue;
            }

            if (array_key_exists($label, $source)) {
                if ($numerical === true) {
                    throw ValidationFailedException::new(ts('The specified version array is invalid, it must be a numerical array with element keys "0 => value, 1 => value, 2 => value" or "major => value, minor => value, revision => value", these keys cannot be mixed'))
                                                   ->addData(['version' => $source]);
                }

                $numerical = false;
            }
        }

        foreach ($source as $section => $version) {
            if ($version < 0) {
                throw ValidationFailedException::new(ts('The specified version array is invalid, key ":section" should be higher than, or equal to 0', [
                    ':section' => $section,
                ]))->addData(['version' => $source]);
            }

            if ($version > 999) {
                throw ValidationFailedException::new(ts('The specified version array is invalid, key ":section" should be lower than, or equal to 999', [
                    ':section' => $section,
                ]))->addData(['version' => $source]);
            }
        }

        return $this;
    }


    /**
     * Returns the source version as an array
     *
     * Array element order:
     * 0 => major
     * 1 => minor
     * 2 => revision
     *
     * @return array
     */
    protected function getSourceAsArray(): array
    {
        return explode('.', $this->source);
    }


    /**
     * Sets the source version as an array
     *
     * This method will convert the given array source to a version string and store it in the source of this object
     *
     * Array element order:
     * 0 => major
     * 1 => minor
     * 2 => revision
     *
     * @param array $source The version in array format to store in this object
     *
     * @return static
     */
    protected function setSourceAsArray(array $source): static
    {
        return $this->validateVersionArray($source)
                    ->setSource(implode('.', $source));
    }


    /**
     * Converts the specified string version to a integer version
     *
     * @param string $version The string version to convert to an integer
     *
     * @return int
     */
    protected static function convertStringToInteger(string $version): int
    {
        switch ($version) {
            case 'post_once':
                return -1;

            case 'post_always':
                return -2;
        }

        if (!Strings::isVersion($version)) {
            throw new OutOfBoundsException(tr('Specified version ":version" is not valid, should be of format "\d{1,4}.\d{1,4}.\d{1,4}"', [
                ':version' => $version,
            ]));
        }

        $major    = (int) Strings::until($version, '.') * 1000000;
        $minor    = (int) Strings::until(Strings::from($version, '.'), '.') * 1000;
        $revision = (int) Strings::fromReverse($version, '.');

        return $major + $minor + $revision;
    }


    /**
     * Compares versions with support for "post", "post_once", "post_always"
     *
     * @param VersionInterface|string $version1               First version to compare
     * @param VersionInterface|string $version2               Second version to compare
     * @param bool                    $short_version1 [false] If true, the first version is expected to be a short version (8.4) instead of a long version (8.4.3)
     * @param bool                    $short_version2 [false] If true, the second version is expected to be a short version (8.4) instead of a long version (8.4.3)
     *
     * @return int
     */
    protected function compare(VersionInterface|string $version1, VersionInterface|string $version2, bool $short_version1 = false, bool $short_version2 = false): int
    {
        // Check if versions are valid
        $version1 = Validate::new($version1)->isVersion(phoundation_versions: true, short_version: $short_version1)->getSource();
        $version2 = Validate::new($version2)->isVersion(phoundation_versions: true, short_version: $short_version2)->getSource();

        // Process if the first version has "post" in it
        switch ($version1) {
            case 'post_once':
                return match ($version2) {
                    'post_always' => -1,
                    'post_once'   => 0,
                    default       => 1,
                };

            case 'post_always':
                return match ($version2) {
                    'post_always' => 0,
                    default       => 1,
                };
        }

        // If the second version has post in it, it is easier as we have already processed all "post" version1
        if (str_starts_with($version2, 'post')) {
            return 1;
        }

        return version_compare($version1 . ($short_version1 ? '.0' : null), $version2 . ($short_version2 ? '.0' : null));
    }


    /**
     * Returns true if $version1 is higher than $version2
     *
     * @param VersionInterface|string $version1               The first version to compare, should be higher to return true
     * @param VersionInterface|string $version2               The second version to compare, should be higher to return false
     * @param bool                    $short_version1 [false] If true, expects a short version (8.4) instead of a long version (8.4.3)
     * @param bool                    $short_version2 [false] If true, expects a short version (8.4) instead of a long version (8.4.3)
     * @param bool                    $or_equal_to    [false] If true, will return true when the specified version is equal to this version
     *
     * @return bool
     */
    protected function __isHigherThan(VersionInterface|string $version1, VersionInterface|string $version2, bool $short_version1 = false, bool $short_version2 = false, bool $or_equal_to = false): bool
    {
        switch ($this->compare($version1, $version2, $short_version1, $short_version2)) {
            case 1:
                return true;

            case 0:
                if ($or_equal_to) {
                    return true;
                }

            // no break

            case -1:
                // no break

            default:
                return false;
        }
    }


    /**
     * Returns true if the specified version is higher than the current version
     *
     * @param VersionInterface|string $version               The version to compare to
     * @param bool                    $or_equal_to   [false] If true, will return true when the specified version is equal to this version
     * @param bool                    $short_version [false] If true will work with short versions (8.4) instead of long versions (8.4.3)
     *
     * @return bool
     */
    public function isHigherThan(VersionInterface|string $version, bool $or_equal_to = false, bool $short_version = false): bool
    {
        return $this->__isHigherThan($this, $version, $this->short_version, $short_version, $or_equal_to);
    }


    /**
     * Returns true if the specified version is lower than the current version
     *
     * @param VersionInterface|string $version               The version to compare to
     * @param bool                    $or_equal_to   [false] If true, will return true when the specified version is equal to this version
     * @param bool                    $short_version [false] If true will work with short versions (8.4) instead of long versions (8.4.3)
     *
     * @return bool
     */
    public function isLowerThan(VersionInterface|string $version, bool $or_equal_to = false, bool $short_version = false): bool
    {
        return $this->__isHigherThan($version, $this, $short_version, $this->short_version, $or_equal_to);
    }
}
