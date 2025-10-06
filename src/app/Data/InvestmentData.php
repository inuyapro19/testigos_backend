<?php

namespace App\Data;

use App\Enums\InvestmentStatus;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;

class InvestmentData extends Data
{
    public function __construct(
        #[Required]
        public int $case_id,
        
        #[Required]
        public int $investor_id,
        
        #[Required, Numeric, Min(1000000)]
        public float $amount,
        
        #[Required, Numeric]
        public float $expected_return_percentage,
        
        #[Required, Numeric]
        public float $expected_return_amount,
        
        public InvestmentStatus $status = InvestmentStatus::PENDING,
        public ?array $payment_data = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        $expectedReturnAmount = ($data['amount'] * $data['expected_return_percentage']) / 100;
        
        return new self(
            case_id: $data['case_id'],
            investor_id: $data['investor_id'],
            amount: $data['amount'],
            expected_return_percentage: $data['expected_return_percentage'],
            expected_return_amount: $expectedReturnAmount,
            status: isset($data['status']) ? InvestmentStatus::from($data['status']) : InvestmentStatus::PENDING,
            payment_data: $data['payment_data'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}