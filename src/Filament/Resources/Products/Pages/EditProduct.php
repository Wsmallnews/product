<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Wsmallnews\Product\Enums\ProductStatus;
use Wsmallnews\Product\Filament\Resources\Products\ProductResource;
use Wsmallnews\Product\Support\ProductSpecService;
use Wsmallnews\Support\Filament\Resources\Concerns\Pages\Scopeable;

class EditProduct extends EditRecord
{
    use Scopeable;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * 回填表单时，从产品记录构建规格 / 变体编辑器 state
     * （详情内容由表单的 contentTypeGroup 通过 content 关联自动回填）。
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge($data, ProductSpecService::toFormState($this->getRecord()));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        if (in_array($data['status'], [ProductStatus::Up, ProductStatus::Hidden]) && blank($record->published_at)) {
            $data['published_at'] = now();
        }

        // 详情内容存 morphOne 关联表（sn_contents），由表单的 contentTypeGroup 自动保存
        return ProductSpecService::strip($data);
    }

    /**
     * 保存产品后，全量替换规格树与变体列表（钩子由 EditRecord::save 触发）。
     */
    protected function afterSave(): void
    {
        // 使用 rawState：规格/变体字段的 visible 在脱水阶段可能被跳过，
        // 而 rawState 始终保留完整表单数据
        $state = $this->form->getRawState();

        ProductSpecService::save($this->getRecord(), $state);
    }
}
