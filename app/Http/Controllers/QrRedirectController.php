<?php

namespace App\Http\Controllers;

use App\Models\QrEvent;
use App\Models\QrLink;
use App\Models\QrPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QrRedirectController extends Controller
{
    public function scan(Request $r, string $code): RedirectResponse
    {
        $p = QrPage::where('code', $code)->where('status', 'active')->with(['links' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])->firstOrFail();
        $this->event($p);

        return redirect()->to(config('qr.frontend_url').'/qr/'.$p->code);
    }

    public function click(Request $r, string $token): RedirectResponse
    {
        $l = QrLink::where('token', $token)->where('is_active', true)->whereHas('qrPage', fn ($q) => $q->where('status', 'active'))->with('qrPage')->firstOrFail();
        $this->event($l->qrPage, $l);

        return redirect()->away($l->destination_url);
    }

    private function event(QrPage $p, ?QrLink $l = null): void
    {
        DB::transaction(function () use ($p, $l) {
            $p->increment($l ? 'total_clicks' : 'total_scans');
            if ($l) {
                $l->increment('total_clicks');
            }QrEvent::create(['qr_page_id' => $p->id, 'qr_link_id' => $l?->id, 'type' => $l ? 'click' : 'scan', 'visitor_key' => hash_hmac('sha256', request()->ip().'|'.now()->toDateString(), config('qr.analytics_hash_key', config('app.key')))]);
        });
    }
}
