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
    .badge { padding: 2px 6px; border-radius: 3px; font-size: 9px; }
</style>
</head>
<body>
<h2>Laporan Permintaan Kendaraan Operasional</h2>
<p class="sub">Sistem Manajemen Transportasi Kantor | Dicetak: {{ now()->format('d M Y H:i') }}</p>

<table>
    <thead>
        <tr>
            <th>No</th><th>Pemohon</th><th>Jabatan</th><th>Tujuan</th>
            <th>Tanggal</th><th>Penumpang</th><th>Urgensi</th><th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($permintaan as $i => $p)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $p->user->nama ?? '-' }}</td>
            <td>{{ $p->user->jabatan ?? '-' }}</td>
            <td>{{ $p->tujuan }}</td>
            <td>{{ $p->tanggal_berangkat->format('d/m/Y') }}</td>
            <td>{{ $p->jumlah_penumpang }}</td>
            <td>{{ $p->urgensi->nama_urgensi ?? '-' }}</td>
            <td>{{ ucfirst($p->status) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
