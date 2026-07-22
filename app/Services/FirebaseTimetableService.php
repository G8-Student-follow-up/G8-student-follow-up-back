<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseTimetableService
{
    /**
     * Trainers and their Google Calendar IDs (sourced from pnc-timetables.vercel.app).
     */
    protected array $trainers = [
        ['id' => 'him',       'name' => 'Him',       'calendar_id' => 'passerellesnumeriques.org_343437393530363136@resource.calendar.google.com',       'color' => '#5C5CEE'],
        ['id' => 'lavy',      'name' => 'Lavy',      'calendar_id' => 'passerellesnumeriques.org_2d3331373838323735363330@resource.calendar.google.com',  'color' => '#10B981'],
        ['id' => 'mengheang', 'name' => 'Mengheang', 'calendar_id' => 'c_1886h9lqonri4ig0noe2vrfvp8fb8@resource.calendar.google.com',                      'color' => '#F59E0B'],
        ['id' => 'rady',      'name' => 'Rady',      'calendar_id' => 'passerellesnumeriques.org_2d3132393337373934393735@resource.calendar.google.com',   'color' => '#EF4444'],
        ['id' => 'yon',       'name' => 'Yon',       'calendar_id' => 'c_1882ckecmfgb0h7fmha0u9t3rbd5g@resource.calendar.google.com',                      'color' => '#8B5CF6'],
        ['id' => 'savoeurn',  'name' => 'Savoeurn',  'calendar_id' => 'passerellesnumeriques.org_3539373731343733353932@resource.calendar.google.com',       'color' => '#EC4899'],
        ['id' => 'ouchi',     'name' => 'Ouchi',     'calendar_id' => 'c_188b20cg9s5uoh12jobk987cfbh2g@resource.calendar.google.com',                       'color' => '#06B6D4'],
        ['id' => 'sokhom',    'name' => 'Sokhom',    'calendar_id' => 'passerellesnumeriques.org_2d3633393338303431343434@resource.calendar.google.com',     'color' => '#F97316'],
        ['id' => 'sreyleap',  'name' => 'Sreyleap',  'calendar_id' => 'c_1884lpdesdih0irbl36ss1j7vt7aq@resource.calendar.google.com',                      'color' => '#14B8A6'],
        ['id' => 'puthy',     'name' => 'Puthy',     'calendar_id' => 'passerellesnumeriques.org_3733323437383733383932@resource.calendar.google.com',       'color' => '#6366F1'],
        ['id' => 'mesa',      'name' => 'Mesa',      'calendar_id' => 'c_1885a09ufiueqj6hn3tv09m5ngs5c@resource.calendar.google.com',                      'color' => '#3B82F6'],
    ];

    /**
     * Class groups and their Google Calendar IDs.
     */
    protected array $classes = [
        ['id' => 'y2a', 'name' => 'WEP/Y2-A', 'calendar_id' => 'c_4641706806a2bc464ecfce3054fce93b2ebd72161c407d074ba961f730e4d793@group.calendar.google.com', 'color' => '#4F46E5', 'room' => 'B12'],
        ['id' => 'y2b', 'name' => 'WEP/Y2-B', 'calendar_id' => 'c_60f24913a85bf20e950e70146ff604d69d14749a316da204fa485e7d6731a12b@group.calendar.google.com', 'color' => '#059669', 'room' => 'B13'],
        ['id' => 'y2c', 'name' => 'WEP/Y2-C', 'calendar_id' => 'c_2da1c687af3c99d3ecaf14bbc5d82b9cc9b0fc75dcca546fd690ea5070091940@group.calendar.google.com', 'color' => '#D97706', 'room' => 'B22'],
        ['id' => 'y1a', 'name' => 'WEP/Y1-A', 'calendar_id' => 'c_dab09f6598d565f82430eaa5c51ab4b01045bb20a340a7273f4a67a911c6f61c@group.calendar.google.com', 'color' => '#DC2626', 'room' => 'A22'],
        ['id' => 'y1b', 'name' => 'WEP/Y1-B', 'calendar_id' => 'c_2e32feb66be240456f961cc2f43c0bbef9c5ff6cbb68abdea333adf1739f9833@group.calendar.google.com', 'color' => '#7C3AED', 'room' => 'B23'],
        ['id' => 'y1c', 'name' => 'WEP/Y1-C', 'calendar_id' => 'c_4f19b4ea9a24523bb7910e4d19bb0f426abdfc3e2b67850a4d5770e4755674db@group.calendar.google.com', 'color' => '#DB2777', 'room' => 'B31'],
        ['id' => 'y1d', 'name' => 'SNA/Y1',   'calendar_id' => 'c_60360764ac056113deb843d3bc1a8dd6e66a0bbddcf4dbd1ff4ea3453417dc3e@group.calendar.google.com', 'color' => '#0891B2', 'room' => 'A21'],
    ];

    protected ?string $calendarApiKey;

    public function __construct()
    {
        $this->calendarApiKey = config('services.pnc_timetable.calendar_api_key');
    }

    // ─────────────────────────────────────────────
    //  PUBLIC API
    // ─────────────────────────────────────────────

    /**
     * Fetch school timetable events for the given month/year from Google Calendar.
     *
     * Queries every PNC trainer + class calendar in parallel and returns
     * individual sessions with real start/end times. Results are cached for 3 hours.
     *
     * Returns [] if the API key is not configured or any request fails —
     * never crashes the calendar.
     */
    public function getEvents(int $month, int $year): array
    {
        if (! $this->calendarApiKey) {
            return [];
        }

        $cacheKey = "pnc_timetable_{$year}_{$month}";

        return Cache::remember($cacheKey, now()->addHours(3), function () use ($month, $year) {
            return $this->fetchAllEvents($month, $year);
        });
    }

    // ─────────────────────────────────────────────
    //  GOOGLE CALENDAR API
    // ─────────────────────────────────────────────

    /**
     * Fetch events from all trainer + class calendars concurrently for a given month.
     */
    protected function fetchAllEvents(int $month, int $year): array
    {
        $timeMin = Carbon::create($year, $month, 1, 0, 0, 0)->toIso8601String();
        $timeMax = Carbon::create($year, $month, 1, 0, 0, 0)->endOfMonth()->toIso8601String();

        // Tag each source so we know which trainer/class the events belong to
        $sources = [];
        foreach ($this->trainers as $t) {
            $t['_is_class'] = false;
            $sources[] = $t;
        }
        foreach ($this->classes as $c) {
            $c['_is_class'] = true;
            $sources[] = $c;
        }

        // Fire all calendar requests concurrently via Http::pool
        $responses = Http::pool(function ($pool) use ($sources, $timeMin, $timeMax) {
            foreach ($sources as $source) {
                $pool->as($source['id'])->timeout(6)->get(
                    'https://www.googleapis.com/calendar/v3/calendars/'
                    . urlencode($source['calendar_id']) . '/events',
                    [
                        'key'          => $this->calendarApiKey,
                        'timeMin'      => $timeMin,
                        'timeMax'      => $timeMax,
                        'singleEvents' => 'true',
                        'orderBy'      => 'startTime',
                        'timeZone'     => 'Asia/Phnom_Penh',
                        'maxResults'   => 500,
                    ]
                );
            }
        });

        $events = [];

        foreach ($sources as $source) {
            $response = $responses[$source['id']] ?? null;

            if (! $response || $response->failed()) {
                Log::debug("Calendar API error for {$source['id']}: "
                    . ($response ? $response->status() : 'no response'));
                continue;
            }

            $items = $response->json('items') ?? [];

            foreach ($items as $ev) {
                $parsed = $this->parseEvent($ev, $source);
                if ($parsed) {
                    $events[] = $parsed;
                }
            }
        }

        // Sort by date then time
        usort($events, fn ($a, $b) => ($a['date'] <=> $b['date']) ?: ($a['time'] <=> $b['time']));

        return $events;
    }

    /**
     * Convert a Google Calendar event + its source metadata into a calendar event.
     */
    protected function parseEvent(array $ev, array $source): ?array
    {
        $summary = $ev['summary'] ?? null;
        $start   = $ev['start'] ?? null;

        if (! $summary || ! $start) {
            return null;
        }

        // Determine the date and time string
        $dateTime = $start['dateTime'] ?? null;  // timed event
        $dateStr  = $start['date'] ?? null;      // all-day event

        if ($dateTime) {
            try {
                $dt = new \DateTime($dateTime);
                $date = $dt->format('Y-m-d');
                $startTime = $dt->format('H:i');

                $endTime = '';
                if (isset($ev['end']['dateTime'])) {
                    $endDt = new \DateTime($ev['end']['dateTime']);
                    $endTime = $endDt->format('H:i');
                }

                $time = $endTime ? "{$startTime} - {$endTime}" : $startTime;
            } catch (\Throwable $e) {
                return null;
            }
        } elseif ($dateStr) {
            $date = $dateStr;
            $time = 'All day';
        } else {
            return null;
        }

        $location = $ev['location'] ?? $source['room'] ?? '';
        $owner    = $source['name'];
        $isClass  = $source['_is_class'];

        return [
            'id'       => "school-{$source['id']}-{$ev['id']}",
            'title'    => $summary,
            'date'     => $date,
            'type'     => $isClass ? 'Class' : 'Training',
            'color'    => $source['color'],
            'time'     => $time,
            'source'   => 'school',
            'trainer'  => $isClass ? '' : $owner,
            'class'    => $isClass ? $owner : '',
            'location' => $location,
        ];
    }
}
