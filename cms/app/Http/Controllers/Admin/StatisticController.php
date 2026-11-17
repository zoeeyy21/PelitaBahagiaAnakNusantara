<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatisticController extends Controller
{
    /**
     * @var array<string, string>
     */
    public const TARGET_FILTERS = [
        'reached' => 'Sudah mencapai target',
        'unreached' => 'Belum mencapai target',
    ];

    /**
     * @var array<string, string>
     */
    public const SORT_OPTIONS = [
        'collected_desc' => 'Donasi terbesar',
        'collected_asc' => 'Donasi terkecil',
    ];

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $targetFilter = array_key_exists($request->query('target'), self::TARGET_FILTERS) ? $request->query('target') : null;
        $sort = array_key_exists($request->query('sort'), self::SORT_OPTIONS) ? $request->query('sort') : 'collected_desc';

        $donationPrograms = Program::query()->where('accepts_donation', true);
        $totalTarget = (int) (clone $donationPrograms)->sum('donation_target');
        $totalCollected = (int) Donation::query()->where('status', 'paid')->sum('amount');
        $programCollected = (int) Donation::query()
            ->where('status', 'paid')
            ->whereIn('program_id', (clone $donationPrograms)->select('id'))
            ->sum('amount');

        $programs = (clone $donationPrograms)
            ->withCollectedAmount()
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->when($targetFilter, fn ($query) => $query->whereDonationTargetReached($targetFilter === 'reached'))
            ->orderBy('collected_amount', $sort === 'collected_asc' ? 'asc' : 'desc')
            ->orderBy('title')
            ->paginate(10)
            ->withQueryString();

        return view('admin.statistics.index', [
            'programs' => $programs,
            'summary' => [
                'total_collected' => $totalCollected,
                'program_collected' => $programCollected,
                'total_target' => $totalTarget,
                'total_remaining' => (clone $donationPrograms)
                    ->withCollectedAmount()
                    ->get(['id', 'donation_target'])
                    ->sum(fn (Program $program): int => $program->remainingDonationAmount()),
                'program_count' => (clone $donationPrograms)->count(),
                'reached_count' => (clone $donationPrograms)->whereDonationTargetReached()->count(),
                'paid_donation_count' => Donation::query()->where('status', 'paid')->count(),
            ],
            'search' => $search,
            'targetFilter' => $targetFilter,
            'sort' => $sort,
        ]);
    }
}
