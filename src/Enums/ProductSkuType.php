<?php

namespace Wsmallnews\Product\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

Enum ProductSkuType :string implements HasLabel, HasIcon, HasColor
{

    use EnumHelper;

    case Single = 'single';

    case Multiple = 'multiple';

    case Unit = 'unit';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Single => '单规格',
            self::Multiple => '多规格',
            self::Unit => '多单位',
        };
    }


    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Single => 'gray',
            self::Multiple => 'warning',
            self::Unit => 'success',
        };
    }


    public function getIcon(): ?string
    {
        return match ($this) {
            self::Single => 'heroicon-m-pencil',
            self::Multiple => 'heroicon-m-eye',
            self::Unit => 'heroicon-m-check',
        };
    }
}