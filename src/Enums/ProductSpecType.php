<?php

namespace Wsmallnews\Product\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

Enum ProductSpecType :string implements HasLabel, HasIcon, HasColor
{
    use EnumHelper;

    case Single = 'single';

    case Multiple = 'multiple';

    case MainMultiple = 'main_multiple';

    case Unit = 'unit';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::Single => '单规格',
            self::Multiple => '多规格',
            self::MainMultiple => '主多规格',
            self::Unit => '多单位',
        };
    }


    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Single => 'gray',
            self::Multiple => 'warning',
            self::MainMultiple => 'info',
            self::Unit => 'success',
        };
    }


    public function getIcon(): string | BackedEnum | Htmlable | null
    {
        return match ($this) {
            self::Single => Heroicon::Bars2,
            self::Multiple => Heroicon::Bars3,
            self::MainMultiple => Heroicon::Bars4,
            self::Unit => Heroicon::Bars3BottomLeft,
        };
    }
}