<?php

namespace Wsmallnews\Product\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum VariantStatus: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Up = 'up';

    case Down = 'down';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::Up => '上架中',
            self::Down => '下架',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Up => 'primary',
            self::Down => 'gray',
        };
    }

    public function getIcon(): string | BackedEnum | Htmlable | null
    {
        return match ($this) {
            self::Up => Heroicon::OutlinedArrowUp,
            self::Down => Heroicon::OutlinedArrowDown,
        };
    }
}
