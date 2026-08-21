<?php

namespace Wsmallnews\Product\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum ProductStockType: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Stock = 'stock';

    case Infinite = 'infinite';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Stock => '库存',
            self::Infinite => '不限库存',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Stock => 'gray',
            self::Infinite => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Stock => 'heroicon-m-pencil',
            self::Infinite => 'heroicon-m-eye',
        };
    }
}
