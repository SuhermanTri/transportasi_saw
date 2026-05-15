<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DistanceService
{
    // Sopo Del Tower, Jakarta Selatan
    private const ORIGIN_LAT = -6.2297;
    private const ORIGIN_LNG = 106.8133;

    // Road-distance multiplier: straight-line × 1.35 ≈ actual road km (urban Jakarta)
    private const ROAD_FACTOR = 1.35;

    private string $apiKey;
    private string $orsBase = 'https://api.openrouteservice.org';

    public function __construct()
    {
        $this->apiKey = trim(config('services.openrouteservice.key', ''));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public entry point
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Calculate driving distance (km) from office to $alamatTujuan.
     *
     * Resolution order:
     *   1. ORS geocoding + ORS routing          (accurate, requires API key)
     *   2. ORS geocoding + haversine            (if routing call fails)
     *   3. Nominatim geocoding + haversine      (free fallback, no key needed)
     *   4. null                                  (address unresolvable by both services)
     *
     * The controller must reject null and show the user an error.
     */
    public function hitungJarak(string $alamatTujuan): ?int
    {
        Log::info('[DistanceService] hitungJarak start.', [
            'alamat_tujuan' => $alamatTujuan,
            'ors_key_set'   => !empty($this->apiKey),
        ]);

        // ── Jalur 1 & 2: ORS (hanya jika API key tersedia) ───────────────
        if (!empty($this->apiKey)) {
            $result = $this->resolveViaORS($alamatTujuan);
            if ($result !== null) {
                return $result;
            }
            Log::warning('[DistanceService] ORS gagal, beralih ke Nominatim.', [
                'alamat_tujuan' => $alamatTujuan,
            ]);
        } else {
            Log::warning('[DistanceService] ORS_API_KEY tidak diset, langsung ke Nominatim.', [
                'alamat_tujuan' => $alamatTujuan,
            ]);
        }

        // ── Jalur 3: Nominatim + haversine ───────────────────────────────
        return $this->resolveViaNominatim($alamatTujuan);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Jalur ORS
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveViaORS(string $alamatTujuan): ?int
    {
        $coords = $this->geocodeORS($alamatTujuan);
        if ($coords === null) {
            return null;
        }

        // [lng, lat] — format ORS
        [$destLng, $destLat] = $coords;

        // Coba routing ORS
        $km = $this->routingORS($destLng, $destLat, $alamatTujuan);
        if ($km !== null) {
            return $km;
        }

        // Routing gagal tapi geocoding berhasil → haversine sebagai fallback
        Log::warning('[DistanceService] ORS routing gagal, pakai haversine dari koordinat ORS.', [
            'dest_lat' => $destLat,
            'dest_lng' => $destLng,
        ]);
        return $this->haversine(self::ORIGIN_LAT, self::ORIGIN_LNG, $destLat, $destLng);
    }

    private function geocodeORS(string $alamat): ?array
    {
        $query = $alamat . ', Indonesia';

        try {
            $response = Http::withHeaders(['Authorization' => $this->apiKey])
                ->timeout(10)
                ->get("{$this->orsBase}/geocode/search", [
                    'text'             => $query,
                    'size'             => 1,
                    'boundary.country' => 'IDN',
                ]);

            Log::info('[DistanceService] ORS geocode response.', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 600),
                'query'  => $query,
            ]);

            if ($response->failed()) {
                Log::error('[DistanceService] ORS geocode HTTP error.', [
                    'status' => $response->status(),
                    'query'  => $query,
                ]);
                return null;
            }

            $features = $response->json('features') ?? [];

            if (empty($features)) {
                Log::warning('[DistanceService] ORS geocode: 0 hasil ditemukan.', [
                    'query' => $query,
                ]);
                return null;
            }

            $coords = $features[0]['geometry']['coordinates']; // [lng, lat]
            Log::info('[DistanceService] ORS geocode berhasil.', [
                'query'       => $query,
                'coordinates' => $coords,
                'label'       => $features[0]['properties']['label'] ?? '-',
            ]);

            return $coords; // [lng, lat]

        } catch (\Throwable $e) {
            Log::error('[DistanceService] ORS geocode exception.', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);
            return null;
        }
    }

    private function routingORS(float $destLng, float $destLat, string $alamat): ?int
    {
        // ORS menerima koordinat dalam format [lng, lat]
        $payload = [
            'coordinates' => [
                [self::ORIGIN_LNG, self::ORIGIN_LAT],
                [$destLng, $destLat],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json, application/geo+json',
            ])
                ->timeout(15)
                ->post("{$this->orsBase}/v2/directions/driving-car", $payload);

            Log::info('[DistanceService] ORS routing response.', [
                'status'     => $response->status(),
                'body'       => substr($response->body(), 0, 600),
                'alamat'     => $alamat,
            ]);

            if ($response->failed()) {
                Log::error('[DistanceService] ORS routing HTTP error.', [
                    'status'  => $response->status(),
                    'alamat'  => $alamat,
                ]);
                return null;
            }

            $data   = $response->json();
            $meters = $data['routes'][0]['summary']['distance'] ?? null;

            if ($meters === null || $meters <= 0) {
                Log::error('[DistanceService] ORS routing: field distance tidak ditemukan atau nol.', [
                    'body'   => substr($response->body(), 0, 400),
                    'alamat' => $alamat,
                ]);
                return null;
            }

            $km = max(1, (int) round($meters / 1000));

            Log::info('[DistanceService] ORS routing berhasil.', [
                'alamat'  => $alamat,
                'meters'  => $meters,
                'km'      => $km,
                'sumber'  => 'ORS routing',
            ]);

            return $km;

        } catch (\Throwable $e) {
            Log::error('[DistanceService] ORS routing exception.', [
                'error'  => $e->getMessage(),
                'alamat' => $alamat,
            ]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Jalur Nominatim (fallback tanpa API key)
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveViaNominatim(string $alamatTujuan): ?int
    {
        $coords = $this->geocodeNominatim($alamatTujuan);

        if ($coords === null) {
            Log::error('[DistanceService] Nominatim juga gagal. Jarak tidak dapat dihitung.', [
                'alamat_tujuan' => $alamatTujuan,
            ]);
            return null;
        }

        // Nominatim mengembalikan [lat, lng]
        [$destLat, $destLng] = $coords;

        $km = $this->haversine(self::ORIGIN_LAT, self::ORIGIN_LNG, $destLat, $destLng);

        Log::info('[DistanceService] Jarak via Nominatim + haversine.', [
            'alamat_tujuan' => $alamatTujuan,
            'km'            => $km,
            'sumber'        => 'Nominatim + haversine (estimasi)',
        ]);

        return $km;
    }

    private function geocodeNominatim(string $alamat): ?array
    {
        $isFirst = true;

        foreach ($this->buildNominatimQueries($alamat) as $query) {
            // Nominatim ToS: max 1 request/detik — delay sebelum setiap retry
            if (!$isFirst) {
                usleep(1_100_000); // 1.1 detik
            }
            $isFirst = false;

            $result = $this->callNominatim($query);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Hasilkan daftar query yang dicoba secara berurutan:
     *   1. [nama jalan] + [kota]   → paling spesifik, paling andal
     *   2. [nama jalan] saja       → fallback tanpa konteks kota
     *   3. [kota] saja             → last resort untuk geocode titik kota
     *
     * Strategi: JANGAN coba membersihkan alamat mentah (rentan empty segment).
     * Ekstrak komponen kunci lalu bangun query dari nol.
     */
    private function buildNominatimQueries(string $alamat): array
    {
        $expanded = $this->expandAbbreviations($alamat);

        // Ambil bagian pertama sebelum koma → nama jalan
        $parts      = array_map('trim', explode(',', $expanded));
        $streetRaw  = $parts[0] ?? '';

        // Bersihkan nomor jalan dan karakter sisa dari nama jalan
        $street = preg_replace('/\bNo\.?\s*[\d\-\/]+\b/i', '', $streetRaw);
        $street = preg_replace('/\bRT[\.\s]*\d+\s*\/\s*RW[\.\s]*\d+\b/i', '', $street);
        $street = trim(preg_replace('/\s{2,}/', ' ', $street), " ,\t");

        // Deteksi kota dari keseluruhan teks
        $city = $this->extractCity($expanded);

        $queries = [];

        if ($street && $city) {
            $queries[] = "$street, $city, Indonesia";
        }
        if ($street) {
            $queries[] = "$street, Indonesia";
        }
        if ($city) {
            $queries[] = "$city, Indonesia";
        }

        $queries = array_values(array_unique(array_filter(
            $queries,
            fn($q) => strlen($q) > 12
        )));

        Log::info('[DistanceService] Nominatim query variants.', [
            'original' => $alamat,
            'street'   => $street,
            'city'     => $city,
            'variants' => $queries,
        ]);

        return $queries;
    }

    /**
     * Deteksi nama kota/wilayah dari string alamat.
     */
    private function extractCity(string $text): ?string
    {
        $cities = [
            'Jakarta Selatan', 'Jakarta Pusat', 'Jakarta Utara',
            'Jakarta Barat',   'Jakarta Timur',
            'Tangerang Selatan', 'Tangerang', 'Bekasi', 'Depok',
            'Bogor', 'Bandung', 'Surabaya', 'Yogyakarta', 'Semarang',
            'Medan', 'Makassar', 'Palembang', 'Batam', 'Pekanbaru',
        ];

        foreach ($cities as $city) {
            if (stripos($text, $city) !== false) {
                return $city;
            }
        }

        return null;
    }

    /**
     * Peta singkatan umum bahasa Indonesia → bentuk lengkap.
     * Nominatim berbasis OSM dan sensitif terhadap nama jalan yang tidak disingkat.
     */
    private function expandAbbreviations(string $text): string
    {
        $map = [
            '/\bJl\b\.?/i'         => 'Jalan',
            '/\bJln\b\.?/i'        => 'Jalan',
            '/\bJend\b\.?/i'       => 'Jenderal',
            '/\bJen\b\.?/i'        => 'Jenderal',
            '/\bLet\b\.?/i'        => 'Letnan',
            '/\bLetjen\b\.?/i'     => 'Letnan Jenderal',
            '/\bMayjen\b\.?/i'     => 'Mayor Jenderal',
            '/\bBrigjen\b\.?/i'    => 'Brigadir Jenderal',
            '/\bKol\b\.?/i'        => 'Kolonel',
            '/\bIr\b\.?(?=\s)/i'   => 'Insinyur',
            '/\bDr\b\.?(?=\s)/i'   => 'Doktor',
            '/\bProf\b\.?/i'       => 'Profesor',
            '/\bHM\b\.?/i'         => 'Haji Muhammad',
            '/\bH\b\.?(?=\s)/i'    => 'Haji',
            '/\bKH\b\.?/i'         => 'Kyai Haji',
            '/\bST\b\.?(?=\s)/i'   => 'Sersan',
            '/\bKec\b\.?/i'        => 'Kecamatan',
            '/\bKel\b\.?/i'        => 'Kelurahan',
            '/\bKby\b\.?/i'        => 'Kebayoran',
            '/\bKota\b/i'          => 'Kota',
            '/\bDKI\b/i'           => 'DKI',
            '/\bTim\b\.?(?=\s|,|$)/i' => 'Timur',
            '/\bBrt\b\.?(?=\s|,|$)/i' => 'Barat',
            '/\bSel\b\.?(?=\s|,|$)/i' => 'Selatan',
            '/\bUtr\b\.?(?=\s|,|$)/i' => 'Utara',
            '/\bTgh\b\.?(?=\s|,|$)/i' => 'Tengah',
            '/\bGd\b\.?(?=\s)/i'   => 'Gedung',
            '/\bGdg\b\.?(?=\s)/i'  => 'Gedung',
            '/\bPdg\b\.?(?=\s)/i'  => 'Padang',
            '/\bRy\b\.?(?=\s)/i'   => 'Raya',
        ];

        return preg_replace(array_keys($map), array_values($map), $text);
    }

    private function callNominatim(string $query): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'TransportasiSAW/1.0 (sistem-internal)',
                'Accept'     => 'application/json',
            ])
                ->timeout(10)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'            => $query,
                    'format'       => 'json',
                    'limit'        => 1,
                    'countrycodes' => 'id',
                ]);

            Log::info('[DistanceService] Nominatim response.', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 600),
                'query'  => $query,
            ]);

            if ($response->failed()) {
                Log::error('[DistanceService] Nominatim HTTP error.', [
                    'status' => $response->status(),
                    'query'  => $query,
                ]);
                return null;
            }

            $results = $response->json();

            if (empty($results) || !isset($results[0]['lat'], $results[0]['lon'])) {
                Log::warning('[DistanceService] Nominatim: 0 hasil.', ['query' => $query]);
                return null;
            }

            $lat = (float) $results[0]['lat'];
            $lng = (float) $results[0]['lon'];

            Log::info('[DistanceService] Nominatim geocode berhasil.', [
                'query'        => $query,
                'lat'          => $lat,
                'lng'          => $lng,
                'display_name' => $results[0]['display_name'] ?? '-',
            ]);

            return [$lat, $lng];

        } catch (\Throwable $e) {
            Log::error('[DistanceService] Nominatim exception.', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Haversine
    // ─────────────────────────────────────────────────────────────────────────

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $R    = 6371.0; // radius bumi (km)
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a       = sin($dLat / 2) ** 2
                 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $straight = 2 * $R * asin(sqrt($a));
        $km       = (int) round($straight * self::ROAD_FACTOR);

        Log::info('[DistanceService] Haversine.', [
            'jarak_lurus_km' => round($straight, 2),
            'road_factor'    => self::ROAD_FACTOR,
            'estimasi_km'    => $km,
        ]);

        return max(1, $km);
    }
}
