<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Log;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        $logs = Log::with('user')
            ->when($search, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('description', 'like', "%{$term}%")
                        ->orWhereHas('user', function ($userQuery) use ($term) {
                            $userQuery->where('name', 'like', "%{$term}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(7)
            ->withQueryString();

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}

