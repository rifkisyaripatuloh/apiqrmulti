<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QrPage;
use Illuminate\Http\JsonResponse;

class PublicQrController extends Controller
{
    public function show(string $code): JsonResponse
    {
        $p = QrPage::where('code', $code)->where('status', 'active')->with(['links' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])->firstOrFail();

        return response()->json(['data' => ['code' => $p->code, 'title' => $p->title, 'description' => $p->description, 'links' => $p->links->map(fn ($l) => ['label' => $l->label, 'icon_key' => $l->icon_key, 'click_url' => route('qr.click', ['token' => $l->token]), 'sort_order' => $l->sort_order])]]);
    }
}
