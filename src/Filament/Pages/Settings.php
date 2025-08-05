<?php

namespace Outerweb\FilamentSettings\Filament\Pages;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasUnsavedDataChangesAlert;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Outerweb\Settings\Models\Setting;
use UnitEnum;

/**
 * @property Schema $form
 */
class Settings extends Page
{
    use HasUnsavedDataChangesAlert;

    public ?array $data = [];

    protected static string | UnitEnum | null $navigationGroup = Heroicon::Cog6Tooth;

//    protected static string $view = 'filament-settings::filament/pages/settings';

    public static function getNavigationLabel() : string
    {
        return __('filament-settings::translations.page.navigation_label');
    }

    public function getLayout() : string
    {
        return static::$layout ?? 'filament-panels::components.layout.index';
    }

    public function getTitle() : string
    {
        return __('filament-settings::translations.page.title');
    }


    public function mount() : void
    {
        $this->fillForm();
    }

    protected function fillForm() : void
    {
        $data = Setting::get();

        $this->callHook('beforeFill');

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    public function save() : void
    {
        if (! $this->canEdit()) {
            return;
        }

        try {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $this->callHook('beforeSave');
            
            $new_data = Arr::dot($data);

            foreach ($new_data as $key => $value) {
                Setting::set($key, $value);
            }

            $this->callHook('afterSave');
        } catch (Halt $exception) {
            return;
        }

        $this->getSavedNotification()?->send();

        if ($redirectUrl = $this->getRedirectUrl()) {
            $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode() && is_app_url($redirectUrl));
        }
    }

    protected function getSavedNotification() : ?Notification
    {
        $title = $this->getSavedNotificationTitle();

        if (blank($title)) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title($this->getSavedNotificationTitle());
    }

    protected function getSavedNotificationTitle() : ?string
    {
        return __('filament-settings::translations.notifications.saved');
    }


    /**
     * @return array<Action | ActionGroup>
     */
    public function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    public function getSaveFormAction(): Action
    {
        $hasFormWrapper = $this->hasFormWrapper();

        return Action::make('save')
            ->label(__('filament-settings::translations.form.actions.save'))
            ->submit($hasFormWrapper ? $this->getSubmitFormLivewireMethodName() : null)
            ->action($hasFormWrapper ? null : $this->getSubmitFormLivewireMethodName())
            ->keyBindings(['mod+s'])
            ->visible($this->canEdit());
    }

    public function getSubmitFormAction(): Action
    {
        return $this->getSaveFormAction();
    }

//    public function defaultForm(Schema $schema): Schema
//    {
//        return $schema
//            ->columns(2)
//            ->disabled(! $this->canEdit())
//            ->inlineLabel($this->hasInlineLabels())
//            ->statePath('data');
//    }

    public function schema() : array|Closure
    {
        return [];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema($this->schema())
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        if (! $this->hasFormWrapper()) {
            return Group::make([
                EmbeddedSchema::make('form'),
                $this->getFormActionsContentComponent(),
            ]);
        }

        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler($this->getSubmitFormLivewireMethodName())
            ->footer([
                $this->getFormActionsContentComponent(),
            ]);
    }

    public function getFormActionsContentComponent(): Component
    {
        return Actions::make($this->getFormActions())
            ->alignment($this->getFormActionsAlignment())
            ->fullWidth($this->hasFullWidthFormActions())
            ->sticky($this->areFormActionsSticky());
    }

    protected function getSubmitFormLivewireMethodName(): string
    {
        return 'save';
    }

    public function hasFormWrapper(): bool
    {
        return true;
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }
    protected function getRedirectUrl() : ?string
    {
        return null;
    }

    public function canEdit(): bool
    {
        return true;
    }

    public function getDefaultTestingSchemaName(): ?string
    {
        return 'form';
    }
}
