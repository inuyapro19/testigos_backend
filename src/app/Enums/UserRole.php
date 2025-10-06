<?php

namespace App\Enums;

enum UserRole: string
{
    case VICTIM = 'victim';
    case LAWYER = 'lawyer';
    case INVESTOR = 'investor';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match($this) {
            self::VICTIM => 'Víctima',
            self::LAWYER => 'Abogado',
            self::INVESTOR => 'Inversionista',
            self::ADMIN => 'Administrador',
        };
    }

    public function permissions(): array
    {
        return match($this) {
            self::VICTIM => [
                'cases.create',
                'cases.view.own',
                'cases.update.own',
                'documents.upload',
            ],
            self::LAWYER => [
                'cases.view.all',
                'cases.evaluate',
                'cases.publish',
                'cases.update',
                'documents.view',
            ],
            self::INVESTOR => [
                'cases.view.published',
                'investments.create',
                'investments.view.own',
                'statistics.view.own',
            ],
            self::ADMIN => [
                'users.manage',
                'cases.manage',
                'investments.manage',
                'statistics.view.all',
                'reports.generate',
            ],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}