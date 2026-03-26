<?php

namespace App\Filament\Pages;

use App\Models\Raffle;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class RifaDestaqueSettings extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationLabel = 'Rifa em Destaque';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Configurações';
    }

    protected string $view = 'filament.pages.rifa-destaque-settings';

    public ?string $rifaDestaqueId = null;

    public function mount(): void
    {
        $this->rifaDestaqueId = Setting::get('rifa_destaque_id');
    }

    public function salvar(): void
    {
        Setting::set('rifa_destaque_id', $this->rifaDestaqueId ?? '');

        //Limpar cache da rifa em destaque
        cache()->forget('rifa_ativa_banner');

        Notification::make()
            ->title('Rifa em destaque atualizada!')
            ->success()
            ->send();
    }

    public function getRifasAtivas(): array
    {
        return Raffle::where('status', 'active')
            ->orderByRaw('draw_date IS NULL ASC, draw_date ASC')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->id => $r->title . ' (R$ ' . number_format($r->ticket_price, 2, ',', '.') . '/cota)'])
            ->toArray();
    }
}
