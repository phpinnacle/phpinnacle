<?php

namespace PHPinnacle\Rosetta\Enums;

enum MetadataKind: string
{
    case Reference = 'reference';
    case Document = 'document';
    case Enumeration = 'enum';
    case AccumulationRegister = 'accumrg';
    case InformationRegister = 'inforg';
    case Section = 'section';

    /** @return list<self> */
    public static function roots(): array
    {
        return [
            self::Reference,
            self::Document,
            self::Enumeration,
            self::AccumulationRegister,
            self::InformationRegister,
        ];
    }

    /** @return list<int> */
    public function identityPath(): array
    {
        return match ($this) {
            self::Reference, self::Document => [2, 10, 2],
            self::Enumeration => [2, 6, 2],
            self::AccumulationRegister => [2, 14, 2],
            self::InformationRegister => [2, 16, 2],
            self::Section => [1, 2, 6, 2],
        };
    }

    /** @return list<int> */
    public function propertyRoots(): array
    {
        return match ($this) {
            self::Reference => [7],
            self::Document => [6],
            self::Enumeration => [],
            self::AccumulationRegister => [6, 7, 8],
            self::InformationRegister => [4, 5, 8],
            self::Section => [3],
        };
    }

    /** @return list<int>|null */
    public function referencePath(): ?array
    {
        return match ($this) {
            self::Reference, self::Document => [2, 4],
            self::Enumeration => [2, 2],
            self::AccumulationRegister, self::InformationRegister, self::Section => null,
        };
    }

    public function storageToken(): string
    {
        return match ($this) {
            self::Reference => 'Reference',
            self::Document => 'Document',
            self::Enumeration => 'Enum',
            self::AccumulationRegister => 'AccumRg',
            self::InformationRegister => 'InfoRg',
            self::Section => 'VT',
        };
    }
}
