<?php

namespace App\Enums;

enum CaseStatus: string
{
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case PUBLISHED = 'published';
    case FUNDED = 'funded';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::SUBMITTED => 'Enviado',
            self::UNDER_REVIEW => 'En Revisión',
            self::APPROVED => 'Aprobado',
            self::PUBLISHED => 'Publicado',
            self::FUNDED => 'Financiado',
            self::IN_PROGRESS => 'En Proceso',
            self::COMPLETED => 'Completado',
            self::REJECTED => 'Rechazado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::SUBMITTED => 'yellow',
            self::UNDER_REVIEW => 'blue',
            self::APPROVED => 'green',
            self::PUBLISHED => 'purple',
            self::FUNDED => 'indigo',
            self::IN_PROGRESS => 'orange',
            self::COMPLETED => 'emerald',
            self::REJECTED => 'red',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match($this) {
            self::SUBMITTED => in_array($status, [self::UNDER_REVIEW, self::REJECTED]),
            self::UNDER_REVIEW => in_array($status, [self::APPROVED, self::REJECTED]),
            self::APPROVED => in_array($status, [self::PUBLISHED]),
            self::PUBLISHED => in_array($status, [self::FUNDED]),
            self::FUNDED => in_array($status, [self::IN_PROGRESS]),
            self::IN_PROGRESS => in_array($status, [self::COMPLETED]),
            self::COMPLETED => false,
            self::REJECTED => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}