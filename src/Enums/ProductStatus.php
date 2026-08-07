<?php

namespace Wsmallnews\Product\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

Enum ProductStatus :string implements HasLabel, HasIcon, HasColor
{
    use EnumHelper;

    case Up = 'up';
    
    case Down = 'down';
    
    case Hidden = 'hidden';
    
    case Draft = 'draft';

    case Scheduled = 'scheduled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Up => '上架中',
            self::Down => '下架',
            self::Hidden => '隐藏',
            self::Draft => '草稿',
            self::Scheduled => '定时发布',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Up => 'primary',
            self::Down => 'danger',
            self::Hidden => 'info',
            self::Draft => 'gray',
            self::Scheduled => 'warning',
        };
    }


    public function getIcon(): string | BackedEnum | Htmlable | null
    {
        return match ($this) {
            self::Up => Heroicon::OutlinedArrowUp,
            self::Down => Heroicon::OutlinedArrowDown,
            self::Hidden => Heroicon::OutlinedEyeSlash,
            self::Draft => Heroicon::OutlinedClipboardDocumentList,
            self::Scheduled => Heroicon::OutlinedClock,
        };
    }
}