<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Saran alamat tujuan (autocomplete) untuk form permintaan.
 * Memakai Photon (OpenStreetMap) — gratis, tanpa API key.
 * Dipanggil lewat backend agar hasil bisa di-cache & tidak membebani layanan.
 */
class LokasiController extends Controller
{
    private const PHOTON_URL = 'https://photon.komoot.io/api/';
    private const BBOX_INDONESIA = '95,-11,141,6';
    private const PUSAT_JAKARTA  = ['lat' => -6.2088, 'lon' => 106.8456];

    public function cari(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 3) {
            return response()->json([]);
        }
        $q = mb_substr($q, 0, 100);

        $hasil = Cache::remember('lokasi:' . md5(mb_strtolower($q)), now()->addDay(), function () use ($q) {
            try {
                $res = Http::timeout(6)
                    ->withHeaders(['User-Agent' => config('app.name', 'transportasi-saw')])
                    ->get(self::PHOTON_URL, [
                        'q'     => $q,
                        'limit' => 8,
                        'bbox'  => self::BBOX_INDONESIA,
                        'lat'   => self::PUSAT_JAKARTA['lat'],
                        'lon'   => self::PUSAT_JAKARTA['lon'],
                    ]);
                if (! $res->ok()) {
                    return null;
                }
            } catch (\Throwable $e) {
                Log::warning('[LokasiController] Pencarian lokasi gagal: ' . $e->getMessage());
                return null;
            }

            return collect($res->json('features') ?? [])
                ->map(fn($f) => $this->label($f['properties'] ?? []))
                ->filter()
                ->unique()
                ->take(6)
                ->values()
                ->all();
        });

        // Jangan cache kegagalan sementara
        if ($hasil === null) {
            Cache::forget('lokasi:' . md5(mb_strtolower($q)));
            return response()->json([]);
        }

        return response()->json($hasil);
    }

    /** Susun label: Nama, Jalan No, Kecamatan/Kota, Provinsi (maks 150 karakter = batas kolom tujuan). */
    private function label(array $p): ?string
    {
        $jalan = trim(($p['street'] ?? '') . ' ' . ($p['housenumber'] ?? ''));
        $bagian = array_filter([
            $p['name'] ?? null,
            $jalan !== '' && $jalan !== ($p['name'] ?? null) ? $jalan : null,
            $p['district'] ?? null,
            $p['city'] ?? ($p['county'] ?? null),
            $p['state'] ?? null,
        ]);

        $label = implode(', ', array_unique($bagian));
        return $label !== '' ? mb_substr($label, 0, 150) : null;
    }
}
