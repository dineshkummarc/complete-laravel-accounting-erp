<?php

namespace ImranDevBd\AiCatalog\Console;

use ImranDevBd\AiCatalog\Facades\AiCatalog;
use Illuminate\Console\Command;

class GenerateCommand extends Command
{
    protected $signature = 'ai-catalog:generate
                            {product_id? : Existing product id to attach}
                            {--keyword= : Keyword or product name}
                            {--text= : Free-form source text}
                            {--image= : Image path or URL}
                            {--url= : Supplier or competitor URL}
                            {--audio= : Voice file or .txt transcript}
                            {--document= : Datasheet path}
                            {--locale= : Output locale}
                            {--locales= : Extra locales, comma-separated}
                            {--tone= : professional|persuasive|minimal|playful|luxury|technical|friendly}
                            {--voice= : Brand voice slug}
                            {--campaign= : Seasonal or campaign overlay}
                            {--region= : Compliance pack (us, eu, uk, bd, medical, global)}
                            {--variants= : Number of A/B variants (0-3)}
                            {--queue : Dispatch to the queue}';

    protected $description = 'Generate a review-ready AI product listing';

    public function handle(): int
    {
        $pending = AiCatalog::pending();

        if ($image = $this->option('image')) {
            $pending->fromImage((string) $image);
        } elseif ($url = $this->option('url')) {
            $pending->fromUrl((string) $url);
        } elseif ($audio = $this->option('audio')) {
            $pending->fromVoice((string) $audio);
        } elseif ($document = $this->option('document')) {
            $pending->fromDocument((string) $document);
        } elseif ($text = $this->option('text')) {
            $pending->fromText((string) $text);
        } elseif ($keyword = $this->option('keyword')) {
            $pending->fromKeyword((string) $keyword);
        } else {
            $this->error('Provide --keyword, --text, --image, --url, --audio, or --document.');

            return self::FAILURE;
        }

        if ($locale = $this->option('locale')) {
            $pending->locale((string) $locale);
        }
        if ($tone = $this->option('tone')) {
            $pending->tone((string) $tone);
        }
        if ($voice = $this->option('voice')) {
            $pending->brandVoice((string) $voice);
        }
        if ($locales = $this->option('locales')) {
            $pending->locales(array_filter(array_map('trim', explode(',', (string) $locales))));
        }
        if ($campaign = $this->option('campaign')) {
            $pending->campaign((string) $campaign);
        }
        if ($region = $this->option('region')) {
            $pending->region((string) $region);
        }
        if ($this->option('variants') !== null) {
            $pending->variants((int) $this->option('variants'));
        }
        if ($productId = $this->argument('product_id')) {
            $pending->forProduct($productId);
        }

        $generation = $this->option('queue')
            ? $pending->generateAsync()
            : $pending->generate();

        $this->info("Generation #{$generation->id} [{$generation->uuid}] status={$generation->status->value}");

        if ($generation->payload) {
            $this->line($generation->listing()->title);
        }

        return self::SUCCESS;
    }
}
