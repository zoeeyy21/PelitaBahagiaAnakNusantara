<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Program;
use App\Services\MidtransService;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
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
     * Unduh donasi sesuai filter yang sedang aktif sebagai CSV (bisa dibuka di Excel).
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->filteredQuery($filters)->with('program')->latest();
        $fileName = 'donasi-'.($filters['start_date'] ?? 'awal').'-sd-'.($filters['end_date'] ?? now()->toDateString()).'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\u{FEFF}");
            fputcsv($handle, ['Tanggal', 'Donatur', 'Anonim', 'No. HP', 'Email', 'Program', 'Nominal', 'Status', 'Metode Pembayaran', 'Dibayar', 'Pesan'], escape: '');

            $query->lazy()->each(function (Donation $donation) use ($handle): void {
                fputcsv($handle, [
                    $donation->created_at->format('Y-m-d H:i'),
                    $donation->donor_name,
                    $donation->is_anonymous ? 'Ya' : 'Tidak',
                    $donation->donor_phone,
                    $donation->donor_email,
                    $donation->program?->title ?? 'Donasi Umum',
                    $donation->amount,
                    $donation->statusLabel(),
                    $donation->paymentMethodLabel(),
                    $donation->paid_at?->format('Y-m-d H:i'),
                    $donation->message,
                ], escape: '');
            });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
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

        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'program' => in_array($program, ['general'], true) || ctype_digit((string) $program) ? (string) $program : null,
            'start_date' => $hasDateFilter ? $this->validDate($request->query('start_date')) : now()->subDays(6)->toDateString(),
            'end_date' => $hasDateFilter ? $this->validDate($request->query('end_date')) : now()->toDateString(),
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
            ->when($filters['start_date'], fn (Builder $query, string $date) => $query->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['end_date'], fn (Builder $query, string $date) => $query->where('created_at', '<=', Carbon::parse($date)->endOfDay()));
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

    public function update(Request $request, Donation $donation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Donation::STATUS_LABELS))],
        ]);

        $donation->update([
            'status' => $validated['status'],
            'paid_at' => $validated['status'] === 'paid' ? now() : null,
        ]);

        return redirect()->route('admin.donations.show', $donation)->with('status', 'Status donasi berhasil diperbarui.');
    }

    public function destroy(Donation $donation): RedirectResponse
    {
        $donation->delete();

        return redirect()->route('admin.donations.index')->with('status', 'Donasi berhasil dihapus.');
    }
}
