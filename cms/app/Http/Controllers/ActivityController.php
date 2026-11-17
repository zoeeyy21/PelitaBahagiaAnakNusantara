<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        return view('public.activities.index', [
            'activities' => Activity::query()
                ->with('program')
                ->published()
                ->latest('published_at')
                ->paginate(9),
        ]);
    }

    public function show(Activity $activity): View
    {
        abort_unless($activity->status === 'published', 404);

        $activity->load(['program', 'galleryItems' => fn ($query) => $query->published()->latest()]);

        return view('public.activities.show', [
            'activity' => $activity,
        ]);
    }
}
