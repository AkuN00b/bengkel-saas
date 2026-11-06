<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $workshop = $request->attributes->get('activeWorkshop');
        $subscription = $workshop->subscriptions()->with('plan')->orderByDesc('starts_at')->orderByDesc('id')->first();
        $now = now();
        $trialDays = null;
        $deadline = null;
        $statusLabel = 'Belum ada paket';

        if ($subscription !== null) {
            $statusLabel = match ($subscription->status) {
                SubscriptionStatus::Trialing => 'Trial berakhir',
                SubscriptionStatus::Active => 'Paket aktif',
                SubscriptionStatus::Expired => 'Kedaluwarsa',
                SubscriptionStatus::Cancelled => 'Dibatalkan',
            };
            if ($subscription->status === SubscriptionStatus::Trialing) {
                $deadline = $subscription->trial_ends_at;
                if ($subscription->ends_at !== null && $subscription->ends_at < $deadline) {
                    $deadline = $subscription->ends_at;
                }
                $trialDays = max(0, (int) ceil($now->diffInSeconds($deadline, false) / 86400));
                if ($subscription->starts_at > $now) {
                    $statusLabel = 'Trial belum dimulai';
                    $trialDays = null;
                } elseif ($deadline > $now) {
                    $statusLabel = 'Trial aktif';
                }
            } elseif ($subscription->status === SubscriptionStatus::Active) {
                if ($subscription->starts_at > $now) {
                    $statusLabel = 'Paket belum dimulai';
                } elseif ($subscription->ends_at !== null && $subscription->ends_at <= $now) {
                    $statusLabel = 'Masa paket berakhir';
                }
            }
        }
        $timezone = in_array($workshop->timezone, DateTimeZone::listIdentifiers(), true) ? $workshop->timezone : 'UTC';

        return view('dashboard', [
            'activeWorkshop' => $workshop, 'subscription' => $subscription,
            'statusLabel' => $statusLabel, 'trialDays' => $trialDays,
            'trialDeadline' => $deadline?->timezone($timezone)->format('d/m/Y H:i'),
            'displayTimezone' => $timezone,
        ]);
    }
}
