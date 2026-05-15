<?php

namespace App\Exports;

use App\Models\Permintaan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPermintaanExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    private int $no = 0;

    public function __construct(
        private readonly ?string $tanggal  = null,
        private readonly ?string $dateFrom = null,
        private readonly ?string $dateTo   = null,
    ) {}

    public function collection()
    {
        $q = Permintaan::with(['user', 'urgensi']);

        if ($this->tanggal) {
            $q->whereDate('tanggal_berangkat', $this->tanggal);
        } elseif ($this->dateFrom && $this->dateTo) {
            $q->whereBetween('tanggal_berangkat', [$this->dateFrom, $this->dateTo]);
        } elseif ($this->dateFrom) {
            $q->whereDate('tanggal_berangkat', '>=', $this->dateFrom);
        } elseif ($this->dateTo) {
            $q->whereDate('tanggal_berangkat', '<=', $this->dateTo);
        }

        return $q->orderByDesc('jam_berangkat')->orderByDesc('created_at')->get();
    }

    public function headings(): array
    {
        return [
            'No', 'Nama Pemohon', 'Jabatan', 'Tujuan',
            'Tanggal Berangkat', 'Jam Berangkat',
            'Tanggal Kembali', 'Jam Kembali',
            'Jumlah Penumpang', 'Urgensi', 'Status', 'Tanggal Pengajuan',
        ];
    }

    public function map($row): array
    {
        $this->no++;
        return [
            $this->no,
            $row->user->nama             ?? '-',
            $row->user->jabatan          ?? '-',
            $row->tujuan,
            $row->tanggal_berangkat->format('d/m/Y'),
            $row->jam_berangkat,
            $row->tanggal_kembali?->format('d/m/Y') ?? '-',
            $row->jam_kembali            ?? '-',
            $row->jumlah_penumpang,
            $row->urgensi->nama_urgensi  ?? '-',
            ucfirst($row->status),
            \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i'),
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
