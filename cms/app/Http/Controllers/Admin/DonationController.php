<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Program;
use App\Services\DonationSpreadsheetExporter;
use App\Services\MidtransService;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DonationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.donations.index', [
            'donations' => $this->filteredQuery($filters)
                ->with('program')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'programs' => Program::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    /**
     * Unduh donasi sesuai filter yang sedang aktif sebagai Excel (.xlsx, default) atau CSV.
     */
    public function export(Request $request, DonationSpreadsheetExporter $spreadsheetExporter): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->filteredQuery($filters)->with('program')->latest();
        $baseFileName = 'donasi-'.($filters['start_date'] ?? 'awal').'-sd-'.($filters['end_date'] ?? now()->toDateString());

        if ($request->query('format') !== 'csv') {
            return response()->streamDownload(
                fn () => $spreadsheetExporter->write($query, $this->filterSummary($filters), 'php://output'),
                $baseFileName.'.xlsx',
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            );
        }

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\u{FEFF}");
            fputcsv($handle, array_keys(DonationSpreadsheetExporter::COLUMNS), escape: '');

            $query->lazy()->each(function (Donation $donation) use ($handle): void {
                fputcsv($handle, [
                    ($donation->donor_name ?? '-').($donation->is_anonymous ? ' (anonim)' : ''),
                    $donation->donor_phone ?? '-',
                    $donation->program?->title ?? 'Donasi Umum',
                    $donation->amount,
                    $donation->created_at->toIndonesianDateTime(),
                ], escape: '');
            });

            fclose($handle);
        }, $baseFileName.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Keterangan filter aktif untuk kepala laporan Excel.
     *
     * @param  array{search: string, program: ?string, start_date: ?string, end_date: ?string}  $filters
     * @return list<string>
     */
    private function filterSummary(array $filters): array
    {
        $formatDate = fn (?string $date, string $fallback): string => $date ? Carbon::parse($date)->toIndonesianDate() : $fallback;

        $program = match ($filters['program']) {
            null => 'Semua program',
            'general' => 'Donasi Umum',
            default => Program::query()->whereKey((int) $filters['program'])->value('title') ?? 'Semua program',
        };

        return array_values(array_filter([
            'Periode: '.$formatDate($filters['start_date'], 'awal').' – '.$formatDate($filters['end_date'], 'sekarang'),
            'Program: '.$program,
            $filters['search'] !== '' ? 'Pencarian: "'.$filters['search'].'"' : null,
        ]));
    }

    /**
     * Filter daftar donasi. Tanpa filter tanggal di URL, default 7 hari terakhir (termasuk hari ini).
     *
     * @return array{search: string, program: ?string, start_date: ?string, end_date: ?string}
     */
    private function filters(Request $request): array
    {
        $hasDateFilter = $request->has('start_date') || $request->has('end_date');
        $program = $request->query('program');
        $today = now(config('app.display_timezone'));

        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'program' => in_array($program, ['general'], true) || ctype_digit((string) $program) ? (string) $program : null,
            'start_date' => $hasDateFilter ? $this->validDate($request->query('start_date')) : $today->copy()->subDays(6)->toDateString(),
            'end_date' => $hasDateFilter ? $this->validDate($request->query('end_date')) : $today->toDateString(),
        ];

        if ($filters['start_date'] && $filters['end_date'] && $filters['end_date'] < $filters['start_date']) {
            [$filters['start_date'], $filters['end_date']] = [$filters['end_date'], $filters['start_date']];
        }

        return $filters;
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->toDateString();
        } catch (InvalidFormatException) {
            return null;
        }
    }

    /**
     * @param  array{search: string, program: ?string, start_date: ?string, end_date: ?string}  $filters
     * @return Builder<Donation>
     */
    private function filteredQuery(array $filters): Builder
    {
        return Donation::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = '%'.$filters['search'].'%';

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('reference_code', 'like', $search)
                        ->orWhere('donor_name', 'like', $search)
                        ->orWhere('donor_phone', 'like', $search)
                        ->orWhereHas('program', fn (Builder $query) => $query->where('title', 'like', $search));
                });
            })
            ->when($filters['program'] === 'general', fn (Builder $query) => $query->whereNull('program_id'))
            ->when($filters['program'] !== null && $filters['program'] !== 'general', fn (Builder $query) => $query->where('program_id', (int) $filters['program']))
            ->when($filters['start_date'], fn (Builder $query, string $date) => $query->where('created_at', '>=', $this->dayBoundary($date)->startOfDay()->utc()))
            ->when($filters['end_date'], fn (Builder $query, string $date) => $query->where('created_at', '<=', $this->dayBoundary($date)->endOfDay()->utc()));
    }

    /**
     * Tanggal filter dibaca sebagai hari di zona tampilan (WIB), lalu dibandingkan dengan created_at yang tersimpan dalam UTC.
     */
    private function dayBoundary(string $date): Carbon
    {
        return Carbon::parse($date, config('app.display_timezone'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.donations.index');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('admin.donations.index');
    }

    public function show(Donation $donation, MidtransService $midtransService): View
    {
        $donation->load('program');

        if ($donation->status === 'pending' && $donation->payment_method !== 'manual_transfer') {
            $midtransService->syncTransactionStatus($donation);
        }

        return view('admin.donations.show', [
            'donation' => $donation,
        ]);
    }

    public function syncMidtrans(Donation $donation, MidtransService $midtransService): RedirectResponse
    {
        $result = $midtransService->syncTransactionStatus($donation);

        return redirect()->route('admin.donations.show', $donation)->with('status', $result['message']);
    }

    public function edit(Donation $donation): RedirectResponse
    {
        return redirect()->route('admin.donations.show', $donation);
    }

    public function destroy(Donation $donation): RedirectResponse
    {
        $donation->delete();

        return redirect()->route('admin.donations.index')->with('status', 'Donasi berhasil dihapus.');
    }
}
