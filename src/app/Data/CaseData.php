<?php

namespace App\Data;

use App\Enums\CaseStatus;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;

class CaseData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $title,
        
        #[Required, StringType, Min(50)]
        public string $description,
        
        #[Required]
        public int $victim_id,
        
        public CaseStatus $status = CaseStatus::SUBMITTED,
        
        #[Required, StringType]
        public string $category,
        
        #[Required, StringType, Max(255)]
        public string $company,
        
        public ?float $funding_goal = null,
        public float $current_funding = 0.0,
        public ?int $success_rate = null,
        public ?float $expected_return = null,
        public ?string $deadline = null,
        public ?string $legal_analysis = null,
        public ?array $evaluation_data = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'],
            victim_id: $data['victim_id'],
            status: isset($data['status']) ? CaseStatus::from($data['status']) : CaseStatus::SUBMITTED,
            category: $data['category'],
            company: $data['company'],
            funding_goal: $data['funding_goal'] ?? null,
            current_funding: $data['current_funding'] ?? 0.0,
            success_rate: $data['success_rate'] ?? null,
            expected_return: $data['expected_return'] ?? null,
            deadline: $data['deadline'] ?? null,
            legal_analysis: $data['legal_analysis'] ?? null,
            evaluation_data: $data['evaluation_data'] ?? null,
        );
    }
}