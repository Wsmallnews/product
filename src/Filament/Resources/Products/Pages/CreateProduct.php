<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Filament\Resources\Products\ProductResource;
use Wsmallnews\Product\Support\ProductSpecService;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class CreateProduct extends CreateRecord
{
    use Scopeable;

    protected static string $resource = ProductResource::class;

    /**
     * Mutate the form data before creating a record.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 合并 scopeinfo 参数
        $data = array_merge($data, static::getScopeable());

        // 合并 publisher 参数
        $admin = Filament::auth()->user();
        $data = array_merge($data, [
            'publisher_type' => $admin->getMorphClass(),
            'publisher_id' => $admin->id,
        ]);

        if (in_array($data['status'], [ProductStatus::Up, ProductStatus::Hidden])) {
            $data['published_at'] = now();
        }

        // 详情内容存 morphOne 关联表（sn_contents），由表单的 contentTypeGroup 自动保存
        // 规格 / 变体数据不属于主表，由 afterCreate 统一保存
        return ProductSpecService::strip(parent::mutateFormDataBeforeCreate($data));
    }

    /**
     * 创建产品后，保存规格变体（钩子由 CreateRecord::create 触发）。
     */
    protected function afterCreate(): void
    {
        // 使用 rawState：规格/变体字段的 visible 在脱水阶段可能被跳过，
        // 而 rawState 始终保留完整表单数据（文件已在 create() 的 getState() 阶段落盘）
        $state = $this->form->getRawState();

        ProductSpecService::save($this->getRecord(), $state);
    }
}
