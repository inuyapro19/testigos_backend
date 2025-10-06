<?php

namespace App\Data;

use App\Enums\UserRole;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;

class UserData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,
        
        #[Required, Email, Max(255)]
        public string $email,
        
        #[Required, StringType, Min(8)]
        public string $password,
        
        #[Required, StringType]
        public string $rut,
        
        #[Required]
        public string $birth_date,
        
        #[Required, StringType]
        public string $address,
        
        #[Required, StringType]
        public string $phone,
        
        #[Required]
        public UserRole $role,
        
        public ?string $avatar = null,
        public bool $is_active = true,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            rut: $data['rut'],
            birth_date: $data['birth_date'],
            address: $data['address'],
            phone: $data['phone'],
            role: UserRole::from($data['role']),
            avatar: $data['avatar'] ?? null,
            is_active: $data['is_active'] ?? true,
        );
    }
}