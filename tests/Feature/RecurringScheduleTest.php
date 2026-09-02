<?php

use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\models\RecurringScheduleModel;

/**
 * Tests recurring sendout schedules.
 */

function recurringScheduleMatchesDate(array $scheduleAttributes, string $sendDate, string $date): bool
{
    $schedule = new class($scheduleAttributes) extends RecurringScheduleModel {
        public function matches(SendoutElement $sendout, DateTime $date): bool
        {
            return $this->matchesDate($sendout, $date);
        }
    };

    $sendout = new SendoutElement([
        'sendDate' => new DateTime($sendDate),
    ]);

    return $schedule->matches($sendout, new DateTime($date));
}

test('A monthly recurring schedule can match specific days of the month', function() {
    $schedule = [
        'frequencyInterval' => 'months',
        'daysOfMonth' => [15 => 1],
    ];

    expect(recurringScheduleMatchesDate($schedule, '2026-01-01', '2026-02-15'))->toBeTrue()
        ->and(recurringScheduleMatchesDate($schedule, '2026-01-01', '2026-02-16'))->toBeFalse();
});

test('A monthly recurring schedule can match the first selected weekday', function() {
    $schedule = [
        'frequencyInterval' => 'months',
        'monthlyScheduleType' => 'daysOfWeek',
        'daysOfWeekInMonth' => [6 => 1],
        'weeksOfMonth' => ['first'],
    ];

    expect(recurringScheduleMatchesDate($schedule, '2026-01-01', '2026-08-01'))->toBeTrue()
        ->and(recurringScheduleMatchesDate($schedule, '2026-01-01', '2026-08-08'))->toBeFalse();
});

test('A fifth selected weekday only matches in months in which it exists', function() {
    $schedule = [
        'frequencyInterval' => 'months',
        'monthlyScheduleType' => 'daysOfWeek',
        'daysOfWeekInMonth' => [6 => 1],
        'weeksOfMonth' => ['fifth'],
    ];

    expect(recurringScheduleMatchesDate($schedule, '2026-01-01', '2026-08-29'))->toBeTrue()
        ->and(recurringScheduleMatchesDate($schedule, '2026-01-01', '2026-09-26'))->toBeFalse();
});

test('The last selected weekday can occur in the fourth or fifth week', function() {
    $schedule = [
        'frequencyInterval' => 'months',
        'monthlyScheduleType' => 'daysOfWeek',
        'daysOfWeekInMonth' => [6 => 1],
        'weeksOfMonth' => ['last'],
    ];

    expect(recurringScheduleMatchesDate($schedule, '2026-01-01', '2026-08-29'))->toBeTrue()
        ->and(recurringScheduleMatchesDate($schedule, '2026-01-01', '2026-09-26'))->toBeTrue()
        ->and(recurringScheduleMatchesDate($schedule, '2026-01-01', '2026-09-19'))->toBeFalse();
});

test('A monthly recurring schedule uses the total number of elapsed months', function() {
    $schedule = [
        'frequency' => 5,
        'frequencyInterval' => 'months',
        'monthlyScheduleType' => 'daysOfWeek',
        'daysOfWeekInMonth' => [6 => 1],
        'weeksOfMonth' => ['first'],
    ];

    expect(recurringScheduleMatchesDate($schedule, '2025-01-01', '2026-01-03'))->toBeFalse()
        ->and(recurringScheduleMatchesDate($schedule, '2025-01-01', '2026-04-04'))->toBeTrue();
});

test('A monthly recurring schedule can match multiple selected weeks', function() {
    $schedule = [
        'frequencyInterval' => 'months',
        'monthlyScheduleType' => 'daysOfWeek',
        'daysOfWeekInMonth' => [6 => 1],
        'weeksOfMonth' => ['first', 'third'],
    ];

    expect(recurringScheduleMatchesDate($schedule, '2026-01-01', '2026-08-01'))->toBeTrue()
        ->and(recurringScheduleMatchesDate($schedule, '2026-01-01', '2026-08-15'))->toBeTrue()
        ->and(recurringScheduleMatchesDate($schedule, '2026-01-01', '2026-08-08'))->toBeFalse();
});
