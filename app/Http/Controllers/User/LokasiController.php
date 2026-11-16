<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Saran alamat tujuan (autocomplete) untuk form permintaan.
 *
 * Sumber data (gratis, tanpa API key, dari OpenStreetMap):
 *   1. Photon    — dirancang untuk pencarian sambil mengetik.
 *   2. Nominatim — cadangan otomatis bila Photon tidak dapat dihubungi.
 *
 * Dipanggil lewat backend agar hasil bisa di-cache & tidak membebani layanan.
 */
class LokasiController extends Controller
{
    private const PHOTON_URL    = 'https://photon.komoot.io/api/';
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';

    private const BBOX_INDONESIA = '95,-11,141,6';
    private const PUSAT_JAKARTA  = ['lat' => -6.2088, 'lon' => 106.8456];

    /** Photon gagal → dilewati selama ini (detik) agar user tidak menunggu timeout berulang. */
    private const JEDA_PHOTON_GAGAL = 600;

    public function cari(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 3) {
            return response()->json([]);
        }
        $q = mb_substr($q, 0, 100);

        // 1. Riwayat tujuan di database — selalu tersedia, tanpa internet
        $lokal = $this->cariRiwayat($q);

        // 2. Peta online (Photon → cadangan Nominatim), di-cache 1 hari per kata kunci
        $online = [];
        try {
            $online = $this->cariOnline($q);
        } catch (\Throwable $e) {
            Log::warning('[LokasiController] Pencarian online gagal: ' . $e->getMessage());
        }

        $hasil = collect($lokal)->merge($online)
            ->unique(fn($s) => mb_strtolower(trim($s)))
            ->take(8)
            ->values()
            ->all();

        return response()->json($hasil);
    }

    /** Tujuan yang pernah diajukan (terbaru dulu) yang mengandung kata kunci. @return string[] */
    private function cariRiwayat(string $q): array
    {
        try {
            $pola = '%' . addcslashes($q, '%_\\') . '%';
            return \App\Models\Permintaan::where('tujuan', 'like', $pola)
                ->groupBy('tujuan')
                ->orderByRaw('MAX(id_permintaan) DESC')
                ->limit(4)
                ->pluck('tujuan')
                ->all();
        } catch (\Throwable $e) {
            Log::warning('[LokasiController] Pencarian riwayat gagal: ' . $e->getMessage());
            return [];
        }
    }

    /** Hasil peta online (cache 1 hari). Kegagalan sementara tidak di-cache. @return string[] */
    private function cariOnline(string $q): array
    {
        $key = 'lokasi:' . md5(mb_strtolower($q));
        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $hasil = $this->cariPhoton($q) ?? $this->cariNominatim($q);
        if ($hasil === null) {
            return [];
        }

        Cache::put($key, $hasil, now()->addDay());
        return $hasil;
    }

    /** @return string[]|null  null = layanan tidak dapat dihubungi */
    private function cariPhoton(string $q): ?array
    {
        if (Cache::has('lokasi:photon_gagal')) {
            return null;
        }

        try {
            $res = Http::timeout(3)->connectTimeout(3)
                ->withHeaders(['User-Agent' => $this->userAgent()])
                ->get(self::PHOTON_URL, [
                    'q'     => $q,
                    'limit' => 8,
                    'bbox'  => self::BBOX_INDONESIA,
                    'lat'   => self::PUSAT_JAKARTA['lat'],
                    'lon'   => self::PUSAT_JAKARTA['lon'],
                ]);
            if (! $res->ok()) {
                throw new \RuntimeException('HTTP ' . $res->status());
            }
        } catch (\Throwable $e) {
            Cache::put('lokasi:photon_gagal', true, self::JEDA_PHOTON_GAGAL);
            Log::warning('[LokasiController] Photon tidak dapat dihubungi, beralih ke Nominatim: ' . $e->getMessage());
            return null;
        }

        return collect($res->json('features') ?? [])
            ->map(fn($f) => $this->susunLabel(
                $f['properties']['name'] ?? null,
                trim(($f['properties']['street'] ?? '') . ' ' . ($f['properties']['housenumber'] ?? '')),
                $f['properties']['district'] ?? null,
                $f['properties']['city'] ?? ($f['properties']['county'] ?? null),
                $f['properties']['state'] ?? null,
            ))
            ->filter()->unique()->take(6)->values()->all();
    }

    /**
     * Nominatim: kebijakan penggunaan → maks. 1 permintaan/detik & User-Agent yang jelas.
     * Hasil di-cache 1 hari, jadi kata kunci yang sama tidak memanggil ulang.
     *
     * @return string[]|null
     */
    private function cariNominatim(string $q): ?array
    {
        // Batas global 1 permintaan per detik ke Nominatim
        if (RateLimiter::tooManyAttempts('lokasi:nominatim', 1)) {
            usleep(max(1, RateLimiter::availableIn('lokasi:nominatim')) * 1_000_000);
        }
        RateLimiter::hit('lokasi:nominatim', 1);

        try {
            $res = Http::timeout(5)->connectTimeout(3)
                ->withHeaders(['User-Agent' => $this->userAgent()])
                ->get(self::NOMINATIM_URL, [
                    'q'               => $q,
                    'format'          => 'jsonv2',
                    'addressdetails'  => 1,
                    'limit'           => 8,
                    'countrycodes'    => 'id',
                    'accept-language' => 'id',
                    // Prioritaskan sekitar Jakarta tanpa membatasi hasil di luar area ini
                    'viewbox'         => '106.60,-6.05,107.10,-6.40',
                    'bounded'         => 0,
                ]);
            if (! $res->ok()) {
                throw new \RuntimeException('HTTP ' . $res->status());
            }
        } catch (\Throwable $e) {
            Log::warning('[LokasiController] Nominatim gagal: ' . $e->getMessage());
            return null;
        }

        return collect($res->json() ?? [])
            ->map(function ($r) {
                $a = $r['address'] ?? [];
                return $this->susunLabel(
                    $r['name'] ?? null,
                    trim(($a['road'] ?? '') . ' ' . ($a['house_number'] ?? '')),
                    $a['suburb'] ?? ($a['village'] ?? ($a['city_district'] ?? null)),
                    $a['city'] ?? ($a['town'] ?? ($a['county'] ?? ($a['regency'] ?? null))),
                    $a['state'] ?? null,
                ) ?? mb_substr((string) ($r['display_name'] ?? ''), 0, 150);
            })
            ->filter()->unique()->take(6)->values()->all();
    }

    /** Label: Nama, Jalan No, Kelurahan/Kecamatan, Kota, Provinsi (maks 150 karakter = batas kolom tujuan). */
    private function susunLabel(?string $nama, ?string $jalan, ?string $wilayah, ?string $kota, ?string $provinsi): ?string
    {
        $bagian = array_filter([
            $nama,
            $jalan !== '' && $jalan !== $nama ? $jalan : null,
            $wilayah,
            $kota,
            $provinsi,
        ]);

        $label = implode(', ', array_unique($bagian));
        return $label !== '' ? mb_substr($label, 0, 150) : null;
    }

    private function userAgent(): string
    {
        return 'transportasi-saw/1.0 (' . config('app.name', 'Sistem Transportasi') . ')';
    }
}
