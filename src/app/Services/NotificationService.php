<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Enums\UserRole;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notifyUser(int $userId, string $title, string $message, string $type = 'system_notification', array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
        ]);
    }

    public function notifyUsers(Collection $userIds, string $title, string $message, string $type = 'system_notification', array $data = []): Collection
    {
        $notifications = collect();

        foreach ($userIds as $userId) {
            $notifications->push($this->notifyUser($userId, $title, $message, $type, $data));
        }

        return $notifications;
    }

    public function notifyAdmins(string $title, string $message, array $data = []): Collection
    {
        $adminIds = User::where('role', UserRole::ADMIN->value)->pluck('id');
        return $this->notifyUsers($adminIds, $title, $message, 'system_notification', $data);
    }

    public function notifyLawyers(string $title, string $message, array $data = []): Collection
    {
        $lawyerIds = User::where('role', UserRole::LAWYER->value)->pluck('id');
        return $this->notifyUsers($lawyerIds, $title, $message, 'case_update', $data);
    }

    public function notifyInvestors(string $title, string $message, array $data = []): Collection
    {
        $investorIds = User::where('role', UserRole::INVESTOR->value)->pluck('id');
        return $this->notifyUsers($investorIds, $title, $message, 'investment_update', $data);
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        return Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]) > 0;
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function getUserNotifications(int $userId, int $limit = 20): Collection
    {
        return Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}