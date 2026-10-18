<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\WalletTransactions\WalletTransactionResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'walletTransactions';

    protected static ?string $title = 'حركات المحفظة';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return WalletTransactionResource::configureLedgerTable($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->with('creator'))
            ->columns(WalletTransactionResource::ledgerColumns());
    }
}
