<?php

namespace Wsmallnews\Product\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum AttributeStatus: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Up = 'up';

    case Down = 'down';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Up => '上架',
            self::Down => '下架',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Up => 'primary',
            self::Down => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Up => 'heroicon-m-arrow-long-up',
            self::Down => 'heroicon-m-arrow-long-down',
        };
    }
}
