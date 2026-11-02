<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $request->query('q');

        $logs = ActivityLog::with('admin:id,name')
            ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('subject_title', 'like', "%{$q}%")
                    ->orWhere('detail', 'like', "%{$q}%")
                    ->orWhereHas('admin', fn ($a) => $a->where('name', 'like', "%{$q}%"));
            }))
            ->latest()
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ActivityLog $l) => $l->toRow());

        return Inertia::render('Admin/Activity/Index', [
            'logs'    => $logs,
            'filters' => ['q' => $q],
        ]);
    }
}