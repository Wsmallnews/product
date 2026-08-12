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

    // 隐藏状态，但是用户找到链接是可以正常购买的
    case Hidden = 'hidden';

    case Draft = 'draft';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Up => '上架中',
            self::Down => '下架',
            self::Hidden => '隐藏',
            self::Draft => '草稿',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Up => 'primary',
            self::Down => 'danger',
            self::Hidden => 'info',
            self::Draft => 'gray',
        };
    }


    public function getIcon(): string | BackedEnum | Htmlable | null
    {
        return match ($this) {
            self::Up => Heroicon::OutlinedArrowUp,
            self::Down => Heroicon::OutlinedArrowDown,
            self::Hidden => Heroicon::OutlinedEyeSlash,
            self::Draft => Heroicon::OutlinedClipboardDocumentList,
        };
    }
}