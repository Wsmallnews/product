<?php

namespace Wsmallnews\Product\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum FormLayout: string implements HasLabel
{
    use EnumHelper;

    case Plain = 'plain';

    case Tabs = 'tabs';

    case Wizard = 'wizard';

    public function getLabel(): string | Htmlable | null
    {
        return match ($this) {
            self::Plain => '普通布局',
            self::Tabs => '标签页布局',
            self::Wizard => '向导布局',
        };
    }
}
