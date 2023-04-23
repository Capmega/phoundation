<?php

namespace Phoundation\Data\Interfaces;


use Phoundation\Accounts\Users\User;
use Phoundation\Core\Meta\Meta;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Date\DateTime;
use Phoundation\Web\Http\Html\Components\DataEntryForm;

/**
 * Class InterfaceDataEntry
 *
 * Interface for DataEntry objects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
interface InterfaceDataEntry
{
    function __construct(InterfaceDataEntry|string|int|null $identifier = null);

    function __toString(): string;

    function __toArray(): array;

    static function new(InterfaceDataEntry|string|int|null $identifier = null): static;

    static function get(InterfaceDataEntry|string|int|null $identifier = null): ?static;

    static function getRandom(): ?static;

    static function exists(string|int $identifier = null, bool $throw_exception = false): bool;

    static function notExists(string|int $identifier = null, ?int $id = null, bool $throw_exception = false): bool;

    static function getAutoComplete(array $auto_complete = []): array;

    function getCliFields(): array;

    static function getHelp(?string $help = null): string;

    function getTable(): string;

    function isNew(): bool;

    function getId(): int|null;

    function getLogId(): string;

    function getStatus(): ?string;

    function setStatus(?String $status, ?string $comments = null): static;

    function getMetaState(): ?string;

    function delete(?string $comments = null): static;

    public function undelete(?string $comments = null): static;

    public function erase(): static;

    public function getCreatedBy(): ?User;

    public function getCreatedOn(): ?DateTime;

    public function getMeta(): ?Meta;

    public function getMetaId(): ?int;

    public function getDiff(): ?string;

    public function create(?array $data = null, bool $no_arguments_left = false): static;

    public function modify(?array $data = null, bool $no_arguments_left = false): static;

    public function getProtectedFields(): array;

    public function getData(): array;

    public function addDataValue(string $key, mixed $value): static;

    public function save(?string $comments = null): static;

    public function getCliForm(?string $key_header = null, ?string $value_header = null): void;

    public function getHtmlForm(): DataEntryForm;

    public function modifyKeys(string $form_key, array $settings): static;
}