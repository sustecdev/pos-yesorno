<?php

namespace App\Enums;

enum KitchenAlertType: string
{
    case NewTicket = 'new_ticket';
    case FireCourse = 'fire_course';
    case OrderModified = 'order_modified';
    case OrderCancelled = 'order_cancelled';
    case RushOrder = 'rush_order';
    case AllergyAlert = 'allergy_alert';
    case SlaBreach = 'sla_breach';
    case RecallTicket = 'recall_ticket';
    case KitchenBroadcast = 'kitchen_broadcast';

    public function priority(): int
    {
        return match ($this) {
            self::AllergyAlert, self::RushOrder => 100,
            self::SlaBreach => 90,
            self::FireCourse => 80,
            self::RecallTicket => 70,
            self::NewTicket => 60,
            self::OrderModified => 50,
            self::OrderCancelled => 40,
            self::KitchenBroadcast => 30,
        };
    }

    public function sound(): string
    {
        return match ($this) {
            self::NewTicket => 'new',
            self::FireCourse => 'fire',
            self::OrderModified => 'modify',
            self::OrderCancelled => 'cancel',
            self::RushOrder => 'rush',
            self::AllergyAlert => 'rush',
            self::SlaBreach => 'modify',
            self::RecallTicket => 'new',
            self::KitchenBroadcast => 'fire',
        };
    }
}
