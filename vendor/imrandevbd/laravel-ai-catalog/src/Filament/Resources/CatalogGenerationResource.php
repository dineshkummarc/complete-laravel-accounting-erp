<?php

namespace ImranDevBd\AiCatalog\Filament\Resources;

use ImranDevBd\AiCatalog\Facades\AiCatalog;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class CatalogGenerationResource extends Resource
{
    protected static ?string $model = CatalogGeneration::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $slug = 'ai-catalog-generations';

    public static function getNavigationGroup(): ?string
    {
        return (string) config('ai-catalog.filament.group', 'AI Catalog');
    }

    public static function getNavigationSort(): ?int
    {
        return (int) config('ai-catalog.filament.sort', 50);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('ai-catalog.filament.navigation', true)
            && (bool) config('ai-catalog.filament.enabled', true);
    }

    public static function form($form)
    {
        return $form->schema([
            Forms\Components\Section::make('Listing')
                ->schema([
                    Forms\Components\TextInput::make('payload.title')->label('Title'),
                    Forms\Components\TextInput::make('payload.meta_title')->label('Meta title'),
                    Forms\Components\Textarea::make('payload.meta_description')->label('Meta description')->rows(2),
                    Forms\Components\Textarea::make('payload.short_description')->label('Short description')->rows(3),
                    Forms\Components\Textarea::make('payload.long_description')->label('Long description')->rows(8),
                    Forms\Components\TagsInput::make('payload.tags')->label('Tags'),
                ]),
            Forms\Components\Section::make('Governance')
                ->schema([
                    Forms\Components\TextInput::make('status')->disabled(),
                    Forms\Components\TextInput::make('overall_confidence')->disabled(),
                    Forms\Components\TextInput::make('locale')->disabled(),
                    Forms\Components\TextInput::make('tone')->disabled(),
                    Forms\Components\KeyValue::make('confidence')->disabled(),
                ]),
        ]);
    }

    public static function table($table)
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('payload.title')->label('Title')->limit(40)->searchable(),
                Tables\Columns\BadgeColumn::make('status')->sortable(),
                Tables\Columns\TextColumn::make('overall_confidence')->label('Confidence')->sortable(),
                Tables\Columns\TextColumn::make('locale'),
                Tables\Columns\TextColumn::make('cost_usd')->label('USD')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->using(function (CatalogGeneration $record, array $data) {
                    $payload = $data['payload'] ?? [];
                    if (is_array($payload) && $payload !== []) {
                        AiCatalog::edit($record, $payload);
                    }

                    return $record->refresh();
                }),
                Tables\Actions\Action::make('approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (CatalogGeneration $record) => AiCatalog::approve($record)),
                Tables\Actions\Action::make('reject')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (CatalogGeneration $record) => AiCatalog::reject($record)),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => CatalogGenerationResource\Pages\ListCatalogGenerations::route('/'),
            'view' => CatalogGenerationResource\Pages\ViewCatalogGeneration::route('/{record}'),
            'edit' => CatalogGenerationResource\Pages\EditCatalogGeneration::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest('id');
    }
}
