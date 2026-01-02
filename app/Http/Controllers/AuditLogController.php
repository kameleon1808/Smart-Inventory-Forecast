<?php

namespace App\Http\Controllers;

use App\Domain\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::with('user')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
        ]);
    }
}
