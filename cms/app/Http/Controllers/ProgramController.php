<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        return view('public.programs.index', [
            'programs' => Program::query()
                ->published()
                ->latest('published_at')
                ->paginate(9),
        ]);
    }

    public function show(Program $program): View
    {
        abort_unless($program->isPubliclyVisible(), 404);

        $program->load([
            'activities' => fn ($query) => $query->published()->latest('published_at')->take(4),
            'galleryItems' => fn ($query) => $query->published()->latest()->take(8),
        ])->loadSum(['donations as collected_amount' => fn ($query) => $query->where('status', 'paid')], 'amount');

        return view('public.programs.show', [
            'program' => $program,
        ]);
    }
}
