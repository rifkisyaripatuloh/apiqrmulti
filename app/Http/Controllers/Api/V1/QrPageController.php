<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QrPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QrPageController extends Controller
{
    private function data(QrPage $page, bool $includeLinks = true): array
    {
        return [
            'id' => $page->id,
            'code' => $page->code,
            'title' => $page->title,
            'description' => $page->description,
            'status' => $page->status,
            'qr_url' => config('qr.public_base_url').'/go/'.rawurlencode($page->code),
            'total_scans' => $page->total_scans,
            'total_clicks' => $page->total_clicks,
            'links' => $includeLinks ? $page->links->map(fn ($link) => [
                'id' => $link->id,
                'token' => $link->token,
                'label' => $link->label,
                'icon_key' => $link->icon_key,
                'destination_url' => $link->destination_url,
                'click_url' => config('qr.public_base_url').'/r/'.rawurlencode($link->token),
                'sort_order' => $link->sort_order,
                'is_active' => $link->is_active,
                'total_clicks' => $link->total_clicks,
            ]) : [],
            'created_at' => $page->created_at,
            'updated_at' => $page->updated_at,
        ];
    }

    private function owned(string $id): QrPage
    {
        return QrPage::with('links')
            ->whereKey($id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    public function index(Request $request)
    {
        $query = QrPage::where('user_id', auth()->id())
            ->withCount(['links as active_links_count' => fn ($links) => $links->where('is_active', true)]);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->string('search').'%');
        }

        $pages = $query->latest()->paginate(min((int) $request->input('per_page', 10), 100));
        $pages->getCollection()->transform(fn ($page) => $this->data($page, false));

        return response()->json($pages);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $page = QrPage::create(array_merge($data, [
            'user_id' => auth()->id(),
            'code' => $this->uniqueCode(),
        ]));

        return response()->json(['data' => $this->data($page->load('links'))], 201);
    }

    public function show(string $qrPage)
    {
        return response()->json(['data' => $this->data($this->owned($qrPage))]);
    }

    public function update(Request $request, string $qrPage)
    {
        $page = $this->owned($qrPage);
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'min:3', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'paused'])],
        ]);

        if (($data['status'] ?? $page->status) === 'active' && ! $page->links()->where('is_active', true)->exists()) {
            return response()->json([
                'message' => 'QR aktif wajib memiliki minimal satu link aktif.',
                'errors' => ['status' => ['Tambahkan link aktif terlebih dahulu.']],
            ], 422);
        }

        $page->update($data);

        return response()->json(['data' => $this->data($page->fresh('links'))]);
    }

    public function destroy(string $qrPage)
    {
        $this->owned($qrPage)->delete();

        return response()->noContent();
    }

    private function uniqueCode(): string
    {
        do {
            $code = Str::random(12);
        } while (QrPage::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
