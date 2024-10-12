<?php

/**
 * Class DataEntry
 *
 * This class is the basic DataEntry class
 *
 * DataEntry are extensions of the Entry object. DataEntry classes reflect a single entry in a specific database table.
 * User DataEntry classes, for example, reflect a single entry from the "accounts_users" table.
 *
 * All DataEntry database rows can always be identified with a unique ID column (typically "id" but this can be
 * configured per extending class to something different) and optionally a unique identifier. For example, the
 * User DataEntry class (representing single entries in the "accounts_users" table) has the id column "id" and the
 * unique identifier "email"
 *
 * DataEntry objects can be created in a number of different ways. Let's assume the Phoundation\Accounts\User class. You
 * can use new User() or User::new(), which returns a new (empty) User. You can also load a specific user by specifying
 * an identifier, in which case the DataEntry will try to load the specified entry IF it exists. Identifiers typically
 * require specifying a colum, though if the column has not been specified, the DataEntry object will assume the class'
 * "id" column if the identifier is numeric, or the unique column if the identifier is a string. If the given identifier
 * does not exist, a new DataEntry object will be returned with the given column set to the identifier ("id" is the
 * single exception to this, as the "id" column can never be changed). For example, new User(2309842), which returns
 * user with ID 2309842 IF this user exists in the database. Another way could be User::new(user@domain.com) which would
 * return the user with the unique identifier "user@domain.com" IF this user exists in the database, or in
 * configuration. For more information about loading DataEntry objects from configuration, see below.
 *
 * All DataEntry objects have a static DataEntry::new() method which is simply a shorthand for new DataEntry(), which
 * allows for direct usage of the said object, not requiring a new line to continue working on the object.
 *
 * A different way to get a new DataEntry object is using the User::load() method. This will load the DataEntry from
 * a database or configuration, and REQUIRE that this entry exists.
 *
 * The final way to get a new DataEntry object is using DataEntry::newFromSource($array) where the source array contains
 * the data for this DataEntry. All source column values will be copied to the inner source, any columns that are not
 * supported will be ignored.
 *
 * Typically, DataEntries have metadata, though this can be disabled on a per extending class basis. Typically, this
 * metadata consists of the following rows (though these rows can, again, be disabled on a per extending class basis):
 *     id           Default unique identifier
 *     created_on   User_id of the user that created this row
 *     created_by   Timestamp that automatically initializes to the current datetime of row creation
 *     meta_id      Link to the meta system that tracks all audit information about this row
 *     status       Current status of this row
 *     meta_state   Random string identifying if the row was modified, used for caching and consistency checks
 *
 * DataEntry rows are typically never deleted. Usually, their status column is updated to "deleted" meaning that the
 * entry will no longer be available to most users (Right "access_deleted" is required to be able to access deleted
 * rows). Attempting to load DataEntries with status "deleted" either through new DataEntry() or DataEntry::load() will
 * result in a DataEntryDeletedException being thrown.
 *
 * The status column typically is NULL, meaning "all normal". The status column may have any status as needed for that
 * table, but a number of often used status values are:
 *     NULL            All is okay, the default status value
 *     deleted         Means this row is deleted, and can no longer be loaded
 *     system          This is a system entry, will be readonly
 *     configuration   This entry was read not from database, but from configuration, and is readonly (see below)
 *     new             This entry is new and requires an update to get into NULL state
 *
 * DataEntry tables typically refer to other tables by ID column. These ID columns are BIGINT. Saving new DataEntry
 * objects will automatically generate a random new ID for this object in the database (unless configured not to do so,
 * on a per extending class basis)
 *
 * DataEntry objects contain a definition of both the table and the structure of said table. This makes it that the
 * entry can read and write data simply with DataEntry::load() or DataEntry::save(). Each DataEntry object will know
 * exactly what columns are available to this entry, and will also know how to properly validate each column. When any
 * value inside the object is changed, the object cannot write to disk without first validating each column value. If
 * any validation fails, a ValidationFailedException will be thrown. DataEntry objects also have definition information
 * on how to display the object in an HTML page, so DataEntry::getHtmlDataEntryFormObject() will return an
 * HtmlDataEntryForm that allows the contents of this DataEntry object to be rendered for a web page correctly and
 * automatically. The definitions also contain information on how to handle command line arguments and even command line
 * auto-suggest, making building scripts to handle these objects straightforward.
 *
 * The definitions also tell the DataEntry object what columns are available. Some DataEntry extending classes will have
 * special get/set handler methods, others won't. All columns are available through DataEntry::get(), or
 * DataEntry::set(), but for example, the User object also has User::getEmail(), User::setNickname(), etc.
 *
 * DataEntry objects can automatically receive and apply GET, POST, or argv data using the GetValidator, PostValidator,
 * or ArgvValidator classes. DataEntry::apply() will automatically select the correct validator to use and applies the
 * data, though data can also be manually specified using an array or ArrayValidator object, with
 * DataEntry::apply([data]). Each column of a DataEntry object can also be specified manually like
 * User::setEmail($email)
 *
 * Saving DataEntry objects will (if enabled, on a per extending class basis) update the meta information for this
 * DataEntry object. The "meta_state" column will be updated, but also a new entry will be added to the "meta_history"
 * table, containing the performed action and a diff of what changes were made when, by whom.
 *
 * If enabled, DataEntry objects will also track the creation of objects, allowing for tracking who accessed what data
 * when. This is beneficial in systems that require audits of who accessed and changed information.
 *
 * Meta-information for each DataEntry object is available with DataEntry::getMetaObject()
 *
 * DataEntry source data (the data stored inside the object) can be accessed (like most other Phoundation objects)
 * through DataEntry::getSource()
 *
 * DataEntry objects also contain information on what database connector to use, so each extended class will always
 * automatically access the DataEntry object from the correct database using the correct credentials. The connector can
 * be overridden by overriding the DataEntry::getConnector() method
 *
 * An entire DataEntry object can be disabled, or made readonly (causing the HTML output to have all fields disabled or
 * readonly) using DataEntry::setReadonly(), DataEntry::getDisabled(), etc.
 *
 * DataEntry tables support loading from the database, but also loading from configuration using Config::getArray()).
 * This requires the DataEntry::$configuration_path to be set and to load the DataEntry either with column null or
 * column equal to the DataEntry::getUniqueColumn() value for that DataEntry class. Please note that configuration
 * loaded DataEntry objects are readonly and do not have any metadata support
 *
 * Multiple DataEntries can be contained in DataIterator class objects of a similar type, usually having the same name
 * but in plural. User class objects, for example, can be stored in a Users class object.
 *
 * @see \Phoundation\Data\Entry
 * @see \Phoundation\Data\DataEntry\DataEntryCore
 * @see \Phoundation\Data\DataEntry\Definitions\Definitions
 * @see \Phoundation\Data\DataEntry\Definitions\Definition
 * @see \Phoundation\Data\DataEntry\DataIterator
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;


class DataEntry extends DataEntryCore
{
    /**
     * Returns a new DataEntry object
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     *
     * @return static
     */
    public static function new(array|DataEntryInterface|string|int|null $identifier = null, ?bool $meta_enabled = null, bool $init = true): static
    {
        return new static($identifier, $meta_enabled, $init);
    }
}
