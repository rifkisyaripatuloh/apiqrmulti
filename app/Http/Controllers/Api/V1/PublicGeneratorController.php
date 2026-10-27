<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QrLink;
use App\Models\QrPage;
use App\Rules\PublicHttpsUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicGeneratorController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'links' => ['required', 'array', 'min:1', 'max:20'],
            'links.*.label' => ['required', 'string', 'min:1', 'max:80'],
            'links.*.icon_key' => ['required', Rule::in(['whatsapp', 'instagram', 'tiktok', 'facebook', 'youtube', 'website', 'custom'])],
            'links.*.destination_url' => ['required', 'string', 'max:2048', new PublicHttpsUrl],
        ]);

        $page = DB::transaction(function () use ($data) {
            $page = QrPage::create([
                'user_id' => null,
                'code' => $this->code(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => 'active',
            ]);
            foreach ($data['links'] as $position => $link) {
                $page->links()->create([...$link, 'token' => $this->token(), 'sort_order' => $position, 'is_active' => true]);
            }
            return $page;
        });

        return response()->json(['data' => ['code' => $page->code, 'qr_url' => config('qr.public_base_url').'/go/'.rawurlencode($page->code), 'public_url' => config('qr.public_base_url').'/q/'.rawurlencode($page->code)]], 201);
    }

    private function code(): string { do { $code = Str::random(12); } while (QrPage::withTrashed()->where('code', $code)->exists()); return $code; }
    private function token(): string { do { $token = Str::random(24); } while (QrLink::withTrashed()->where('token', $token)->exists()); return $token; }
}
