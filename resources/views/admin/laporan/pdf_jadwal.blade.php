<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; }
    h2 { text-align: center; margin-bottom: 5px; }
    p.sub { text-align: center; margin: 0 0 15px; color: #555; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 5px 7px; }
    th { background: #1a237e; color: white; text-align: left; }
    tr:nth-child(even) { background: #f5f5f5; }
</style>
</head>
<body>
<h2>Laporan Jadwal Penggunaan Kendaraan Operasional</h2>
<p class="sub">Sistem Manajemen Transportasi Kantor | Dicetak: {{ now()->format('d M Y H:i') }}</p>

<table>
    <thead>
        <tr>
            <th>No</th><th>Pemohon</th><th>Tujuan</th><th>Kendaraan</th>
            <th>No Polisi</th><th>Tanggal</th><th>Jam Mulai</th><th>Jam Selesai</th><th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($jadwal as $i => $j)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $j->permintaan->user->nama ?? '-' }}</td>
            <td>{{ $j->permintaan->tujuan ?? '-' }}</td>
            <td>{{ $j->kendaraan->nama_kendaraan ?? '-' }}</td>
            <td>{{ $j->kendaraan->no_polisi ?? '-' }}</td>
            <td>{{ \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') }}</td>
            <td>{{ $j->jam_mulai }}</td>
            <td>{{ $j->jam_selesai }}</td>
            <td>{{ ucfirst($j->status) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
