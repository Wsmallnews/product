<?php

namespace Wsmallnews\Product\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum ProductStatus: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Up = 'up';

    case Down = 'down';

    // 隐藏状态，但是用户找到链接是可以正常购买的
    case Hidden = 'hidden';

    case Draft = 'draft';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::Up => __('sn-product::product.product_status.up'),
            self::Down => __('sn-product::product.product_status.down'),
            self::Hidden => __('sn-product::product.product_status.hidden'),
            self::Draft => __('sn-product::product.product_status.draft'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Up => 'primary',
            self::Down => 'danger',
            self::Hidden => 'gray',
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
