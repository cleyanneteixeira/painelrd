<?php

namespace App\Filament\Resources\DestinoPremiado\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RaffleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('Título da rifa')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state)))
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label('Slug (URL)')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => 'Rascunho',
                        'active'    => 'Ativo',
                        'drawing'   => 'Em sorteio',
                        'finished'  => 'Finalizado',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('draft')
                    ->required(),

                TextInput::make('ticket_price')
                    ->label('Valor da cota (R$)')
                    ->numeric()
                    ->required()
                    ->prefix('R$')
                    ->minValue(0.01),

                TextInput::make('total_tickets')
                    ->label('Total de cotas')
                    ->numeric()
                    ->required()
                    ->minValue(1),

                DateTimePicker::make('draw_date')
                    ->label('Data do sorteio')
                    ->nullable()
                    ->displayFormat('d/m/Y H:i')
                    ->helperText('Deixe em branco para sortear automaticamente quando todas as cotas forem vendidas.')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Descrição')
                    ->rows(4)
                    ->nullable()
                    ->columnSpanFull(),

                FileUpload::make('image')
                    ->label('Imagem principal')
                    ->image()
                    ->directory('destino-premiado')
                    ->nullable()
                    ->columnSpanFull(),

                //Prêmios múltiplos
                Repeater::make('prizes')
                    ->label('Prêmios')
                    ->relationship('prizes')
                    ->schema([
                        TextInput::make('position')
                            ->label('Posição')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->helperText('1 = 1º lugar, 2 = 2º lugar...'),
                        TextInput::make('title')
                            ->label('Nome do prêmio')
                            ->required()
                            ->placeholder('Ex: Pacote para os Lençóis Maranhenses'),
                        Textarea::make('description')
                            ->label('Descrição do prêmio')
                            ->rows(2)
                            ->nullable(),
                        FileUpload::make('image')
                            ->label('Foto do prêmio')
                            ->image()
                            ->directory('destino-premiado/premios')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->orderColumn('position')
                    ->addActionLabel('+ Adicionar prêmio')
                    ->helperText('Adicione um ou mais prêmios. Se não adicionar nenhum, o prêmio será exibido pela descrição geral.')
                    ->columnSpanFull(),
            ]);
    }
}
