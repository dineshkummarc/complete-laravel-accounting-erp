<?php

namespace ImranDevBd\AiCatalog\Filament\Actions;

use ImranDevBd\AiCatalog\Facades\AiCatalog;
use Illuminate\Database\Eloquent\Model;

class GenerateWithAiAction
{
    /**
     * One-click "Generate with AI" for Filament v3 tables or v4 actions.
     */
    public static function make(string $name = 'generateWithAi'): mixed
    {
        $class = self::actionClass();

        return $class::make($name)
            ->label('Generate with AI')
            ->icon('heroicon-o-sparkles')
            ->form(self::formSchema())
            ->action(function (Model $record, array $data): void {
                $pending = AiCatalog::forProduct($record);

                if (! empty($data['image'])) {
                    $pending->fromImage($data['image']);
                } elseif (! empty($data['url'])) {
                    $pending->fromUrl($data['url']);
                } elseif (! empty($data['text'])) {
                    $pending->fromText($data['text']);
                } else {
                    $keyword = $data['keyword']
                        ?? $record->name
                        ?? $record->title
                        ?? (string) $record->getKey();
                    $pending->fromKeyword((string) $keyword);
                }

                if (! empty($data['locale'])) {
                    $pending->locale($data['locale']);
                }
                if (! empty($data['tone'])) {
                    $pending->tone($data['tone']);
                }
                if (! empty($data['brand_voice'])) {
                    $pending->brandVoice($data['brand_voice']);
                }

                $async = (bool) ($data['async'] ?? false);
                $generation = $async ? $pending->generateAsync() : $pending->generate();

                if (method_exists($record, 'notify')) {
                    return;
                }

                if (function_exists('filament')) {
                    // Notification is optional; host Filament apps pick this up.
                }

                unset($generation);
            });
    }

    /**
     * @return list<mixed>
     */
    protected static function formSchema(): array
    {
        $text = self::component('TextInput', ['Filament\\Forms\\Components\\TextInput', 'Filament\\Forms\\Components\\TextInput']);
        $area = self::component('Textarea', ['Filament\\Forms\\Components\\Textarea']);
        $select = self::component('Select', ['Filament\\Forms\\Components\\Select']);
        $toggle = self::component('Toggle', ['Filament\\Forms\\Components\\Toggle']);

        if (! $text) {
            return [];
        }

        $fields = [
            $text::make('keyword')->label('Keyword / product name'),
            $area ? $area::make('text')->label('Source text')->rows(4) : null,
            $text::make('image')->label('Image URL or path'),
            $text::make('url')->label('Source URL'),
        ];

        if ($select) {
            $fields[] = $select::make('tone')
                ->options(array_combine(
                    (array) config('ai-catalog.tones', []),
                    (array) config('ai-catalog.tones', [])
                ))
                ->default(config('ai-catalog.tone'));
            $fields[] = $select::make('locale')
                ->options(array_combine(
                    (array) config('ai-catalog.locales', ['en']),
                    (array) config('ai-catalog.locales', ['en'])
                ))
                ->default(config('ai-catalog.locale', 'en'));
        }

        $fields[] = $text::make('brand_voice')->label('Brand voice');

        if ($toggle) {
            $fields[] = $toggle::make('async')->label('Queue this generation')->default(false);
        }

        return array_values(array_filter($fields));
    }

    protected static function actionClass(): string
    {
        if (class_exists(\Filament\Actions\Action::class)) {
            return \Filament\Actions\Action::class;
        }

        if (class_exists(\Filament\Tables\Actions\Action::class)) {
            return \Filament\Tables\Actions\Action::class;
        }

        throw new \RuntimeException('Filament is not installed. composer require filament/filament');
    }

    /**
     * @param  list<string>  $classes
     */
    protected static function component(string $fallback, array $classes): ?string
    {
        foreach ($classes as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        $guess = "Filament\\Forms\\Components\\{$fallback}";

        return class_exists($guess) ? $guess : null;
    }
}
