<?php

namespace App\Exports;

use App\Models\Jadwal;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanJadwalExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    private int $no = 0;

    public function __construct(
        private readonly ?string $tanggal  = null,
        private readonly ?string $dateFrom = null,
        private readonly ?string $dateTo   = null,
    ) {}

    public function collection()
    {
        $q = Jadwal::with(['permintaan.user', 'kendaraan']);

        if ($this->tanggal) {
            $q->whereDate('tanggal', $this->tanggal);
        } elseif ($this->dateFrom && $this->dateTo) {
            $q->whereBetween('tanggal', [$this->dateFrom, $this->dateTo]);
        } elseif ($this->dateFrom) {
            $q->whereDate('tanggal', '>=', $this->dateFrom);
        } elseif ($this->dateTo) {
            $q->whereDate('tanggal', '<=', $this->dateTo);
        }

        return $q->orderByDesc('tanggal')->orderByDesc('jam_mulai')->get();
    }

    public function headings(): array
    {
        return [
            'No', 'Pemohon', 'Jabatan', 'Tujuan',
            'Kendaraan', 'No Polisi', 'Driver',
            'Tanggal', 'Jam Mulai', 'Jam Selesai', 'Status',
        ];
    }

    public function map($row): array
    {
        $this->no++;
        return [
            $this->no,
            $row->permintaan->user->nama     ?? '-',
            $row->permintaan->user->jabatan  ?? '-',
            $row->permintaan->tujuan         ?? '-',
            $row->kendaraan->nama_kendaraan  ?? '-',
            $row->kendaraan->no_polisi       ?? '-',
            $row->kendaraan->nama_driver     ?? '-',
            \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y'),
            $row->jam_mulai,
            $row->jam_selesai,
            ucfirst($row->status),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A237E']],
            ],
        ];
    }
}
