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

class QrLinkController extends Controller
{
    private function page(string $id): QrPage
    {
        return QrPage::whereKey($id)->where('user_id', auth()->id())->firstOrFail();
    }

    private function out(QrLink $l): array
    {
        return ['id' => $l->id, 'token' => $l->token, 'label' => $l->label, 'icon_key' => $l->icon_key, 'destination_url' => $l->destination_url, 'click_url' => config('qr.public_base_url').'/r/'.rawurlencode($l->token), 'sort_order' => $l->sort_order, 'is_active' => $l->is_active, 'total_clicks' => $l->total_clicks];
    }

    private function rules(bool $patch = false): array
    {
        $r = ['label' => ['string', 'min:1', 'max:80'], 'icon_key' => [Rule::in(['whatsapp', 'instagram', 'tiktok', 'facebook', 'youtube', 'website', 'custom'])], 'destination_url' => ['string', 'max:2048', new PublicHttpsUrl], 'is_active' => ['boolean']];

        return $patch ? array_map(fn ($x) => array_merge(['sometimes'], $x), $r) : array_merge($r, ['label' => ['required', ...$r['label']], 'icon_key' => ['required', ...$r['icon_key']], 'destination_url' => ['required', ...$r['destination_url']], 'is_active' => ['required', 'boolean']]);
    }

    public function store(Request $r, string $qrPage)
    {
        $p = $this->page($qrPage);
        $d = $r->validate($this->rules());
        $l = DB::transaction(function () use ($p, $d) {
            $p = QrPage::whereKey($p->id)->lockForUpdate()->first();
            if ($p->links()->count() >= config('qr.max_links')) {
                abort(422, 'Batas link tercapai.');
            }$d['token'] = $this->token();
            $d['sort_order'] = (int) $p->links()->max('sort_order') + 1;

            return $p->links()->create($d);
        });

        return response()->json(['data' => $this->out($l)], 201);
    }

    public function update(Request $r, string $qrPage, string $link)
    {
        $p = $this->page($qrPage);
        $l = $p->links()->whereKey($link)->firstOrFail();
        $d = $r->validate($this->rules(true));
        if (array_key_exists('is_active', $d) && ! $d['is_active'] && $p->status === 'active' && $l->is_active && ! $p->links()->where('is_active', true)->whereKeyNot($l->id)->exists()) {
            return response()->json(['message' => 'Tidak dapat menonaktifkan link aktif terakhir.'], 422);
        }$l->update($d);

        return response()->json(['data' => $this->out($l)]);
    }

    public function destroy(string $qrPage, string $link)
    {
        $p = $this->page($qrPage);
        $l = $p->links()->whereKey($link)->firstOrFail();
        if ($p->status === 'active' && $l->is_active && $p->links()->where('is_active', true)->whereKeyNot($l->id)->doesntExist()) {
            return response()->json(['message' => 'Tidak dapat menghapus link aktif terakhir.'], 422);
        }$l->delete();

        return response()->noContent();
    }

    public function reorder(Request $r, string $qrPage)
    {
        $p = $this->page($qrPage);
        $ids = $r->validate(['ids' => 'required|array', 'ids.*' => 'required|string|distinct'])['ids'];
        $actual = $p->links()->pluck('id')->sort()->values()->all();
        $given = $ids;
        sort($given);
        if ($actual !== $given) {
            return response()->json(['message' => 'ids harus memuat semua link tepat satu kali.'], 422);
        }foreach ($ids as $i => $id) {
            $p->links()->whereKey($id)->update(['sort_order' => $i]);
        }

return response()->json(['data' => $p->links()->orderBy('sort_order')->get()->map(fn ($l) => $this->out($l))]);
    }

    private function token(): string
    {
        do {
            $t = Str::random(24);
        } while (QrLink::withTrashed()->where('token', $t)->exists());

        return $t;
    }
}
