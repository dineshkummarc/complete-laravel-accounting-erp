<?php

declare(strict_types=1);

namespace App\Modules\Accounting\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Context\CustomContext;
use Brick\Money\Money;
use InvalidArgumentException;

final readonly class Amount
{
    public BigDecimal $value;

    public function __construct(string $value)
    {
        if (! preg_match('/^\d{1,16}(?:\.\d{1,4})?$/D', $value)) {
            throw new InvalidArgumentException('Amount must fit DECIMAL(20,4).');
        }
        $this->value = BigDecimal::of($value)->toScale(4);
    }

    public function money(string $currency): Money
    {
        return Money::of((string) $this->value, $currency, new CustomContext(4));
    }

    public function base(string $rate): string
    {
        $base = $this->value->multipliedBy($rate)->toScale(4, RoundingMode::HalfUp);

        return (string) (new self((string) $base))->value;
    }
}
