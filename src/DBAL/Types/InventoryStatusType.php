<?php
namespace App\DBAL\Types;

use App\Enum\InventoryStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class InventoryStatusType extends StringType
{
    public const NAME = 'InventoryStatus';

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value instanceof InventoryStatus ? $value->value : $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}