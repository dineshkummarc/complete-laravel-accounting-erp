<?php

namespace ImranDevBd\AiCatalog\Enums;

enum Tone: string
{
    case Professional = 'professional';
    case Persuasive = 'persuasive';
    case Minimal = 'minimal';
    case Playful = 'playful';
    case Luxury = 'luxury';
    case Technical = 'technical';
    case Friendly = 'friendly';

    public function instruction(): string
    {
        return match ($this) {
            self::Professional => 'Write in a clear, professional catalog voice. Precise, trustworthy, no slang.',
            self::Persuasive => 'Write to convert. Benefit-led, sensory, still honest. No false claims.',
            self::Minimal => 'Write short, clean copy. Few adjectives. Let the product speak.',
            self::Playful => 'Write with warmth and light personality. Still accurate. No memes.',
            self::Luxury => 'Write like a quiet luxury house. Sparse, tactile, never shouty or discount-coded.',
            self::Technical => 'Write for spec buyers. Precise measurements, materials, compatibility. No fluff.',
            self::Friendly => 'Write as a helpful store associate. Warm, plain language, still accurate.',
        };
    }
}
