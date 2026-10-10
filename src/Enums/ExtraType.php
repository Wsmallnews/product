<?php

namespace Wsmallnews\Product\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum ExtraType: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Single = 'single';

    case Multiple = 'multiple';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::Single => __('sn-product::product.extra_type.single'),
            self::Multiple => __('sn-product::product.extra_type.multiple'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Single => 'info',
            self::Multiple => 'warning',
        };
    }

    public function getIcon(): string | BackedEnum | Htmlable | null
    {
        return match ($this) {
            self::Single => Heroicon::OutlinedMinusCircle,
            self::Multiple => Heroicon::OutlinedSquares2x2,
        };
    }
}
