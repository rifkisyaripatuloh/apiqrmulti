<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QrEvent;
use App\Models\QrPage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class QrStatsController extends Controller
{
    public function show(Request $r, string $qrPage)
    {
        $p = QrPage::whereKey($qrPage)->where('user_id', auth()->id())->firstOrFail();
        $from = Carbon::parse($r->input('from', now()->subDays(29)->toDateString()))->startOfDay();
        $to = Carbon::parse($r->input('to', now()->toDateString()))->endOfDay();
        $events = QrEvent::where('qr_page_id', $p->id)->whereBetween('created_at', [$from, $to])->get();
        $days = [];
        for ($d = $from->copy(); $d <= $to; $d->addDay()) {
            $days[$d->toDateString()] = ['date' => $d->toDateString(), 'scans' => 0, 'clicks' => 0];
        }foreach ($events as $e) {
            $days[$e->created_at->toDateString()][$e->type.'s']++;
        }

return response()->json(['data' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'total_scans' => $events->where('type', 'scan')->count(), 'total_clicks' => $events->where('type', 'click')->count(), 'series' => array_values($days), 'top_links' => $p->links()->orderByDesc('total_clicks')->limit(10)->get(['id', 'label', 'total_clicks'])]]);
    }
}
