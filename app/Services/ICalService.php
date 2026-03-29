<?php

namespace App\Services;

use Carbon\Carbon;

class ICalService
{
    /**
     * Generate an RFC 5545 iCalendar meeting request string.
     *
     * @param  string  $uid            Stable unique ID — same UID on update overwrites the event in the recipient's calendar
     * @param  string  $title          Event summary / subject line
     * @param  Carbon  $start          Start datetime (any timezone — converted to UTC internally)
     * @param  Carbon  $end            End datetime
     * @param  string  $organizerEmail Organizer address (RSVP replies go here)
     * @param  string  $organizerName  Organizer display name
     * @param  array   $attendees      [['email' => '...', 'name' => '...'], ...]
     * @param  string  $location       Optional location string
     * @param  string  $description    Optional plain-text description
     * @param  string  $method         VCALENDAR METHOD — REQUEST (invite/update) or CANCEL
     */
    public function generate(
        string $uid,
        string $title,
        Carbon $start,
        Carbon $end,
        string $organizerEmail,
        string $organizerName,
        array $attendees = [],
        string $location = '',
        string $description = '',
        string $method = 'REQUEST',
    ): string {
        $now     = Carbon::now()->utc()->format('Ymd\THis\Z');
        $dtStart = $start->copy()->utc()->format('Ymd\THis\Z');
        $dtEnd   = $end->copy()->utc()->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//RM Flooring//Floor Manager//EN',
            'CALSCALE:GREGORIAN',
            "METHOD:{$method}",
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$now}",
            "DTSTART:{$dtStart}",
            "DTEND:{$dtEnd}",
            $this->prop('SUMMARY', $title),
            $this->foldLine("ORGANIZER;CN=\"{$organizerName}\":MAILTO:{$organizerEmail}"),
        ];

        if (filled($location)) {
            $lines[] = $this->prop('LOCATION', $location);
        }

        if (filled($description)) {
            $lines[] = $this->prop('DESCRIPTION', $description);
        }

        foreach ($attendees as $a) {
            $name    = $a['name'] ?? $a['email'];
            $lines[] = $this->foldLine("ATTENDEE;RSVP=TRUE;CN=\"{$name}\":MAILTO:{$a['email']}");
        }

        $lines[] = 'SEQUENCE:0';
        $lines[] = 'STATUS:CONFIRMED';
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Build a property line with escaping and folding. */
    private function prop(string $name, string $value): string
    {
        return $this->foldLine($name . ':' . $this->escape($value));
    }

    /** Escape special characters per RFC 5545 §3.3.11. */
    private function escape(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(["\r\n", "\r", "\n"], '\\n', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        return $text;
    }

    /** Fold lines longer than 75 octets per RFC 5545 §3.1. */
    private function foldLine(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $result = '';
        while (strlen($line) > 75) {
            $result .= substr($line, 0, 75) . "\r\n ";
            $line    = substr($line, 75);
        }

        return $result . $line;
    }
}
