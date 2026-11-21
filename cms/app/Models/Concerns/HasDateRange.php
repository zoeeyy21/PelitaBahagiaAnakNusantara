<?php

namespace App\Models\Concerns;

/**
 * Model dengan kolom "start_date" dan "end_date" (tanpa jam).
 */
trait HasDateRange
{
    /**
     * Label rentang tanggal, mis. "1 Okt 2026 – 5 Okt 2026" atau "1 Okt 2026".
     * Tanpa $format, memakai format yang sama dengan kalender filter admin.
     */
    public function dateRangeLabel(?string $format = null): ?string
    {
        $label = fn ($date): string => $format === null ? $date->toIndonesianDate() : $date->translatedFormat($format);

        if (! $this->start_date) {
            return $this->end_date ? $label($this->end_date) : null;
        }

        if (! $this->end_date || $this->end_date->isSameDay($this->start_date)) {
            return $label($this->start_date);
        }

        return $label($this->start_date).' – '.$label($this->end_date);
    }
}
