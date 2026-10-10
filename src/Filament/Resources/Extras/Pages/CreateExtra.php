<?php

namespace Wsmallnews\Product\Filament\Resources\Extras\Pages;

use Filament\Resources\Pages\CreateRecord;
use Wsmallnews\Product\Filament\Resources\Extras\ExtraResource;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class CreateExtra extends CreateRecord
{
    use Scopeable;

    protected static string $resource = ExtraResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 组行落消费模块 scopeable（选项行随 children 关联继承库归属，scope 留空）
        return array_merge($data, $this->getScopeable());
    }
}
