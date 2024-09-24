<?php

namespace Wsmallnews\Product\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

Enum ProductStatus :string implements HasLabel, HasIcon, HasColor
{

    use EnumHelper;

    case Up = 'up';

    case Down = 'down';

    case Hidden = 'hidden';


    public function getLabel(): ?string
    {
        return match ($this) {
            self::Up => '上架中',
            self::Down => '下架',
            self::Hidden => '隐藏',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Up => 'primary',
            self::Down => 'gray',
            self::Hidden => 'success',
        };
    }


    public function getIcon(): ?string
    {
        return match ($this) {
            self::Up => 'heroicon-m-arrow-long-up',
            self::Down => 'heroicon-m-arrow-long-down',
            self::Hidden => 'heroicon-m-eye-slash',
        };
    }
}