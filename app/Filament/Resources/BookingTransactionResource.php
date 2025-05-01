<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingTransactionResource\Pages;
use App\Filament\Resources\BookingTransactionResource\RelationManagers;
use App\Models\BookingTransaction;
use App\Models\Cosmetic;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Livewire\Notifications;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingTransactionResource extends Resource
{
    protected static ?string $model = BookingTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Customer';

    public static function updateTotals(Get $get, Set $set): void
    {
        $selectedCosmetics = collect($get('transactionDetails'))->filter(fn($item)
        => !empty($item['cosmetic_id']) && !empty($item['quantity']));

        $prices = Cosmetic::find($selectedCosmetics->pluck('cosmetic_id'))->pluck('price', 'id');

        $subtotal = $selectedCosmetics->reduce(function($subtotal,$item) use ($prices) {
            return $subtotal + ($prices[$item['cosmetic_id']] * $item['quantity']);
        }, 0);

        $total_tax_amount = round($subtotal * 0.11);
        $total_amount = round($subtotal + $total_tax_amount);
        $total_quantity = $selectedCosmetics->sum('quantity');

        $set('total_amount', number_format($total_amount, 0, '.', ''));
        $set('total_tax_amount', number_format($total_tax_amount, 0, '.', ''));
        $set('sub_total_amount', number_format($subtotal, 0, '.', ''));
        $set('quantity', $total_quantity);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Product and Price')
                        ->completedIcon('heroicon-m-hand-thumb-up')
                        ->description('Add your product items')
                        ->schema([
                            Grid::make(2)
                            ->schema([

                                Forms\Components\Repeater::make('transactionDetails')
                                ->relationship('transactionDetails')
                                ->schema([

                                    Forms\Components\Select::make('cosmetic_id')
                                    ->relationship('cosmetic', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Select Product')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set){
                                        $cosmetic = Cosmetic::find($state);
                                        $set('price', $cosmetic ? $cosmetic->price : 0);
                                    }),

                                    Forms\Components\TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->readOnly()
                                    ->label('Price')
                                    ->hint('Price will be filled automatically based on product selection'),

                                    Forms\Components\TextInput::make('quantity')
                                    ->integer()
                                    ->default(1)
                                    ->required()
                                ])
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updateTotals($get, $set);
                                })
                                ->minItems(1)
                                ->columnSpan('full')
                                ->label('Choose Products')
                            ]),

                            Grid::make(4)
                            ->schema([

                                Forms\Components\TextInput::make('quantity')
                                ->integer()
                                ->label('Total Quantity')
                                ->readOnly()
                                ->default(1)
                                ->required(),

                                Forms\Components\TextInput::make('sub_total_amount')
                                ->numeric()
                                ->readOnly()
                                ->label('Sub Total Amount'),

                                Forms\Components\TextInput::make('total_amount')
                                ->numeric()
                                ->readOnly()
                                ->label('Total Amount'),

                                Forms\Components\TextInput::make('total_tax_amount')
                                ->numeric()
                                ->readOnly()
                                ->label('Total Tax (11%)'),
                            ])
                    ]),

                    Forms\Components\Wizard\Step::make('Customer Information')
                    ->completedIcon('heroicon-m-hand-thumb-up')
                    ->description('For our marketing')
                    ->schema([

                        Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                            Forms\Components\TextInput::make('phone')
                            ->required()
                            ->maxLength(255),

                            Forms\Components\TextInput::make('email')
                            ->required()
                            ->maxLength(255),

                        ]),
                     ]),


                    Forms\Components\Wizard\Step::make('Delivery Information')
                    ->completedIcon('heroicon-m-hand-thumb-up')
                    ->description('Put your correct Address')
                    ->schema([

                        Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('city')
                            ->required()
                            ->maxLength(255),

                            Forms\Components\TextInput::make('post_code')
                            ->required()
                            ->maxLength(255),

                            Forms\Components\Textarea::make('address')
                            ->required()
                            ->maxLength(255),

                        ]),
                     ]),


                    Forms\Components\Wizard\Step::make('Payment Information')
                    ->completedIcon('heroicon-m-hand-thumb-up')
                    ->description('Review your Payment')
                    ->schema([

                        Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('booking_trx_id')
                            ->required()
                            ->maxLength(255),

                            ToggleButtons::make('is_paid')
                                ->label('Have you paid?')
                                ->boolean()
                                ->grouped()
                                ->icons([
                                    true => 'heroicon-o-pencil',
                                    false => 'heroicon-o-clock',
                                ])
                                ->required(),

                            Forms\Components\FileUpload::make('proof')
                                ->image()
                                ->required(),
                        ]),
                     ]),
                ])
                ->columnSpan('full')
                ->columns(1)
                ->skippable()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('booking_trx_id')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at'),

                Tables\Columns\IconColumn::make('is_paid')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->boolean('Terverifikasi')

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),


                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->action(function (BookingTransaction $record) {
                        $record->is_paid = true;
                        $record->save();

                        Notification::make()
                            ->title('Order Approved')
                            ->success()
                            ->body('The Order has been successfully approved.')
                            ->send();
                    })
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BookingTransaction $record) => !$record->is_paid)


            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookingTransactions::route('/'),
            'create' => Pages\CreateBookingTransaction::route('/create'),
            'edit' => Pages\EditBookingTransaction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
