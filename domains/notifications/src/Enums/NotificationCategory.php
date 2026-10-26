<?php

namespace Modules\Notifications\Enums;

enum NotificationCategory: string
{
    case DueSoon = 'due_soon';
    case RequestUpdate = 'request_update';
    case Overdue = 'overdue';
    case Penalty = 'penalty';
    case HoldAvailable = 'hold_available';
    case Recommendation = 'recommendation';

    /**
     * Mandatory alerts (FR-NOT-03): always in-app + ≥1 external channel.
     *
     * @return list<self>
     */
    public static function mandatory(): array
    {
        return [
            self::DueSoon,
            self::RequestUpdate,
            self::Overdue,
            self::Penalty,
            self::HoldAvailable,
            self::Recommendation,
        ];
    }

    public function isMandatory(): bool
    {
        return in_array($this, self::mandatory(), true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
