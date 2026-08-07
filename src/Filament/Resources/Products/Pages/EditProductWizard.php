<?php

namespace Wsmallnews\Product\Filament\Resources\Products\Pages;

use Filament\Actions;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Wizard;
use Wsmallnews\Product\Filament\Resources\Products\Schemas\ProductForm;
use Wsmallnews\Product\Support\Utils;

class EditProductWizard extends EditProduct
{
    use HasWizard;

    public function getSteps(): array
    {
        return ProductForm::wizardSteps();
    }

    public function getWizardComponent(): Component
    {
        $wizard = Wizard::make($this->getSteps())
            ->startOnStep($this->getStartStep())
            ->cancelAction($this->getCancelFormAction())
            ->submitAction($this->getSubmitFormAction())
            ->alpineSubmitHandler("\$wire.{$this->getSubmitFormLivewireMethodName()}()")
            ->skippable($this->hasSkippableSteps())
            ->contained(false);

        // persist_step 配置
        $persistStep = Utils::getConfig('form_layout.wizard.persist_step', 'step');
        if ($persistStep) {
            $wizard->persistStepInQueryString($persistStep);
        }

        // hidden_header 配置
        if (Utils::getConfig('form_layout.wizard.hidden_header', false)) {
            $wizard->hiddenHeader();
        }

        return $wizard;
    }

    protected function hasSkippableSteps(): bool
    {
        return Utils::getConfig('form_layout.wizard.skippable', true);
    }

    public function getStartStep(): int
    {
        return Utils::getConfig('form_layout.wizard.start_on_step', 1);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
