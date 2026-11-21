<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Laporan donasi dalam format Excel (.xlsx): judul & info filter, tabel dengan header
 * berwarna, filter kolom, header terkunci saat scroll, format Rupiah, dan baris total.
 */
class DonationSpreadsheetExporter
{
    /**
     * Judul kolom beserta lebarnya (dalam karakter). Sama dengan tabel di halaman Donasi.
     *
     * @var array<string, int>
     */
    public const COLUMNS = [
        'Donatur' => 32,
        'No. HP' => 18,
        'Program' => 34,
        'Nominal' => 18,
        'Tanggal' => 26,
    ];

    private const HEADER_ROW = 5;

    private const BRAND_COLOR = '9A3B1F';

    private const RUPIAH_FORMAT = '"Rp"#,##0';

    /**
     * Tulis laporan ke path tujuan (mis. php://output).
     *
     * @param  Builder<Donation>  $query
     * @param  list<string>  $filterSummary  Baris keterangan filter yang sedang aktif.
     */
    public function write(Builder $query, array $filterSummary, string $outputPath): void
    {
        $lastColumn = count(self::COLUMNS) - 1;

        $options = new Options;
        $options->mergeCells(0, 1, $lastColumn, 1);
        $options->mergeCells(0, 2, $lastColumn, 2);
        $options->mergeCells(0, 3, $lastColumn, 3);

        $writer = new Writer($options);
        $writer->openToFile($outputPath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Donasi');
        $sheet->setSheetView((new SheetView)->withFreezeRow(self::HEADER_ROW + 1));

        foreach (array_values(self::COLUMNS) as $index => $width) {
            $sheet->setColumnWidth($width, $index + 1);
        }

        $writer->addRow(Row::fromValuesWithStyle(['Laporan Donasi — Yayasan Pelita Bahagia Anak Nusantara'], (new Style)->withFontBold(true)->withFontSize(14)->withFontColor(self::BRAND_COLOR), 24));
        $writer->addRow(Row::fromValuesWithStyle([implode('  ·  ', $filterSummary)], (new Style)->withFontColor('57534E')));
        $writer->addRow(Row::fromValuesWithStyle(['Diunduh: '.now()->toIndonesianDateTime()], (new Style)->withFontColor('78716C')->withFontItalic(true)));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValuesWithStyle(array_keys(self::COLUMNS), $this->headerStyle(), 22));

        $cellStyle = $this->bodyStyle();
        $donorStyle = $cellStyle->withFontBold(true);
        $amountStyle = $cellStyle->withFormat(self::RUPIAH_FORMAT)->withFontBold(true)->withCellAlignment(CellAlignment::RIGHT);
        $count = 0;

        $query->lazy()->each(function (Donation $donation) use ($writer, $cellStyle, $donorStyle, $amountStyle, &$count): void {
            $count++;

            $writer->addRow(new Row([
                Cell::fromValue(($donation->donor_name ?? '-').($donation->is_anonymous ? ' (anonim)' : ''), $donorStyle),
                Cell::fromValue($donation->donor_phone ?? '-', $cellStyle),
                Cell::fromValue($donation->program?->title ?? 'Donasi Umum', $cellStyle),
                Cell::fromValue($donation->amount, $amountStyle),
                Cell::fromValue($donation->created_at->toIndonesianDateTime(), $cellStyle),
            ]));
        });

        $firstDataRow = self::HEADER_ROW + 1;
        $lastDataRow = self::HEADER_ROW + $count;
        $totalStyle = $this->headerStyle()->withFontColor('1C1917')->withBackgroundColor('F5E6DF');

        $writer->addRow(new Row([
            Cell::fromValue('Total ('.$count.' donasi)', $totalStyle->withCellAlignment(CellAlignment::LEFT)),
            Cell::fromValue('', $totalStyle),
            Cell::fromValue('', $totalStyle),
            Cell::fromValue($count > 0 ? "=SUM(D{$firstDataRow}:D{$lastDataRow})" : 0, $totalStyle->withFormat(self::RUPIAH_FORMAT)->withCellAlignment(CellAlignment::RIGHT)),
            Cell::fromValue('', $totalStyle),
        ], 22));

        if ($count > 0) {
            $sheet->setAutoFilter(new AutoFilter(0, self::HEADER_ROW, $lastColumn, $lastDataRow));
        }

        $writer->close();
    }

    private function headerStyle(): Style
    {
        return (new Style)
            ->withFontBold(true)
            ->withFontColor('FFFFFF')
            ->withBackgroundColor(self::BRAND_COLOR)
            ->withCellAlignment(CellAlignment::CENTER)
            ->withCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->withBorder($this->border());
    }

    private function bodyStyle(): Style
    {
        return (new Style)
            ->withCellVerticalAlignment(CellVerticalAlignment::TOP)
            ->withBorder($this->border());
    }

    private function border(): Border
    {
        return new Border(...array_map(
            fn (BorderName $name): BorderPart => new BorderPart($name, 'D6D3D1'),
            BorderName::cases(),
        ));
    }
}
