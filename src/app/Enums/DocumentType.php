<?php

namespace App\Enums;

enum DocumentType: string
{
    case EVIDENCE = 'evidence';
    case CONTRACT = 'contract';
    case CORRESPONDENCE = 'correspondence';
    case RECEIPT = 'receipt';
    case PHOTO = 'photo';
    case VIDEO = 'video';
    case AUDIO = 'audio';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::EVIDENCE => 'Evidencia',
            self::CONTRACT => 'Contrato',
            self::CORRESPONDENCE => 'Correspondencia',
            self::RECEIPT => 'Recibo',
            self::PHOTO => 'Fotografía',
            self::VIDEO => 'Video',
            self::AUDIO => 'Audio',
            self::OTHER => 'Otro',
        };
    }

    public function allowedMimeTypes(): array
    {
        return match($this) {
            self::EVIDENCE, self::CONTRACT, self::CORRESPONDENCE => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            self::RECEIPT => [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ],
            self::PHOTO => [
                'image/jpeg',
                'image/png',
                'image/gif',
            ],
            self::VIDEO => [
                'video/mp4',
                'video/avi',
                'video/quicktime',
            ],
            self::AUDIO => [
                'audio/mpeg',
                'audio/wav',
                'audio/mp3',
            ],
            self::OTHER => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}