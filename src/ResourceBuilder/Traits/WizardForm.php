<?php

namespace Wsmallnews\Product\ResourceBuilder\Traits;

use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Resources\Pages\Concerns\HasWizard;
use Wsmallnews\Product\ResourceBuilder\ProductResourceBuilder;

trait WizardForm
{
    use HasWizard;

    public function form(Form $form): Form
    {
        return parent::form($form)
            ->schema([
                Components\Wizard::make($this->getSteps())
                    ->startOnStep($this->getStartStep())
                    ->cancelAction($this->getCancelFormAction())
                    ->submitAction($this->getSubmitFormAction())
                    ->skippable($this->hasSkippableSteps())
                    ->contained(false)
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }



    protected function getSteps(): array
    {
        return (new ProductResourceBuilder())->getWizardSteps();
    }
}
