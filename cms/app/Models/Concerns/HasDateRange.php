<?php

namespace App\Models\Concerns;

/**
 * Model dengan kolom "start_date" dan "end_date" (tanpa jam).
 */
trait HasDateRange
{
    /**
     * Label rentang tanggal, mis. "1 – 5 Okt 2026" atau "1 Okt 2026".
     */
    public function dateRangeLabel(string $format = 'd M Y'): ?string
    {
        if (! $this->start_date) {
            return $this->end_date?->translatedFormat($format);
        }

        if (! $this->end_date || $this->end_date->isSameDay($this->start_date)) {
            return $this->start_date->translatedFormat($format);
        }

        return $this->start_date->translatedFormat($format).' – '.$this->end_date->translatedFormat($format);
    }
}
