<?php

namespace App\Services;

class CouponEvaluationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $error,
        public readonly ?object $coupon,
        public readonly int $discountCents,
        public readonly ?string $summary,
        public readonly array $eligibleProductIds = [],
        public readonly bool $isUniversal = false,
    ) {
    }

    public static function valid(?object $coupon, int $discountCents, ?string $summary, array $eligibleProductIds = [], bool $isUniversal = false): self
    {
        return new self(true, null, $coupon, max(0, $discountCents), $summary, array_values($eligibleProductIds), $isUniversal);
    }

    public static function invalid(?string $error, ?object $coupon = null): self
    {
        return new self(false, $error, $coupon, 0, null, [], false);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }
}
