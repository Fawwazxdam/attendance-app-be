<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, WithHeadings, WithStyles
{
    protected $attendances;
    protected $date;

    public function __construct($attendances, $date)
    {
        $this->attendances = $attendances;
        $this->date = $date;
    }

    public function collection()
    {
        return $this->attendances->map(function ($attendance) {
            $punishmentCount = isset($attendance->punishmentRecords) ? $attendance->punishmentRecords->count() : 0;
            $mediaCount = isset($attendance->medias) ? $attendance->medias->count() : 0;

            return [
                'Date' => $this->date,
                'Student Name' => $attendance->student ? $attendance->student->fullname : 'N/A',
                'Grade' => $attendance->student && $attendance->student->grade ? $attendance->student->grade->name : 'N/A',
                'Status' => $attendance->attendance_status,
                'Attendance Time' => $attendance->created_at ? \Carbon\Carbon::parse($attendance->created_at)->format('H:i:s') : 'N/A',
                'Points Earned' => $attendance->points_earned,
                'Total Points' => $attendance->student && $attendance->student->studentPoint ? $attendance->student->studentPoint->total_points : ($attendance->user ? 'N/A' : 0),
                'Remarks' => $attendance->remarks ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama',
            'Kelas',
            'Status Kehadiran',
            'Waktu Absesnsi',
            'Poin Diterima',
            'Total Poin',
            'Deskripsi',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->attendances->count() + 1; // +1 for header row
        $lastColumn = count($this->headings());

        // Apply solid borders to all cells
        $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumn) . $lastRow)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Style header row
        $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumn) . '1')
            ->getFont()
            ->setBold(true);

        // Auto-size columns
        for ($i = 1; $i <= $lastColumn; $i++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        return [];
    }
}