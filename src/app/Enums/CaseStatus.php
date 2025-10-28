<?php

namespace App\Enums;

enum CaseStatus: string
{
    case SUBMITTED = 'submitted';                      // Víctima envió caso
    case UNDER_ADMIN_REVIEW = 'under_admin_review';   // Admin revisando
    case APPROVED_FOR_BIDDING = 'approved_for_bidding'; // Aprobado, esperando licitaciones
    case RECEIVING_BIDS = 'receiving_bids';           // Recibiendo propuestas activamente
    case BIDS_CLOSED = 'bids_closed';                 // Licitación cerrada, admin evaluando
    case LAWYER_ASSIGNED = 'lawyer_assigned';         // Admin asignó abogado ganador
    case PUBLISHED = 'published';                      // Publicado para INVERSORES
    case FUNDED = 'funded';                            // Completamente financiado
    case IN_PROGRESS = 'in_progress';                 // En proceso legal
    case COMPLETED = 'completed';                      // Completado
    case REJECTED = 'rejected';                        // Rechazado por admin (terminal)

    public function label(): string
    {
        return match($this) {
            self::SUBMITTED => 'Enviado',
            self::UNDER_ADMIN_REVIEW => 'En Revisión Admin',
            self::APPROVED_FOR_BIDDING => 'Aprobado para Licitación',
            self::RECEIVING_BIDS => 'Recibiendo Licitaciones',
            self::BIDS_CLOSED => 'Licitación Cerrada',
            self::LAWYER_ASSIGNED => 'Abogado Asignado',
            self::PUBLISHED => 'Publicado para Inversores',
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
            self::UNDER_ADMIN_REVIEW => 'blue',
            self::APPROVED_FOR_BIDDING => 'cyan',
            self::RECEIVING_BIDS => 'teal',
            self::BIDS_CLOSED => 'sky',
            self::LAWYER_ASSIGNED => 'green',
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
            self::SUBMITTED => in_array($status, [
                self::UNDER_ADMIN_REVIEW,
                self::REJECTED
            ]),
            self::UNDER_ADMIN_REVIEW => in_array($status, [
                self::APPROVED_FOR_BIDDING,
                self::REJECTED
            ]),
            self::APPROVED_FOR_BIDDING => in_array($status, [
                self::RECEIVING_BIDS,
                self::REJECTED
            ]),
            self::RECEIVING_BIDS => in_array($status, [
                self::BIDS_CLOSED
            ]),
            self::BIDS_CLOSED => in_array($status, [
                self::LAWYER_ASSIGNED,
                self::APPROVED_FOR_BIDDING // Reabrir licitación si no hay buenos candidatos
            ]),
            self::LAWYER_ASSIGNED => in_array($status, [
                self::PUBLISHED
            ]),
            self::PUBLISHED => in_array($status, [
                self::FUNDED
            ]),
            self::FUNDED => in_array($status, [
                self::IN_PROGRESS
            ]),
            self::IN_PROGRESS => in_array($status, [
                self::COMPLETED
            ]),
            self::COMPLETED => false,
            self::REJECTED => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}