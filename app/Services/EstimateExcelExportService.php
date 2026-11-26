<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\FreightItem;
use App\Models\LabourType;
use App\Models\ProductType;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EstimateExcelExportService
{
    private const HEADERS = [
        'Material', 'Description', 'XA Cat', 'XA Selector', 'Unit',
        'Quantity', 'Unit Price', 'Extended', 'Action',
    ];

    public function build(Estimate $estimate): Spreadsheet
    {
        $estimate->loadMissing(['rooms.items']);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Estimate');

        $headerRow = 1;
        $sheet->fromArray(self::HEADERS, null, 'A' . $headerRow);
        $sheet->getStyle('A' . $headerRow . ':I' . $headerRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
        ]);

        $row = $headerRow + 1;

        // Admin → Settings → Excel Export (Xactimate): XA codes and description
        // override per product type (material rows) and per freight item (freight
        // rows), keyed by name because estimate items store these as text, not ids.
        $fields    = ['xa_cat', 'xa_selector', 'xa_description_override'];
        $normalize = fn (?string $name) => mb_strtolower(trim((string) $name));
        $xaByType    = ProductType::get(['name', ...$fields])->keyBy(fn ($t) => $normalize($t->name));
        $xaByLabour  = LabourType::get(['name', ...$fields])->keyBy(fn ($l) => $normalize($l->name));
        $xaByFreight = FreightItem::get(['description', ...$fields])->keyBy(fn ($f) => $normalize($f->description));

        foreach ($estimate->rooms as $room) {
            foreach ($room->items as $item) {
                [$material, $description, $action] = $this->describeItem($item);

                // Anything not configured stays blank / keeps the item's own description.
                $xa = match ($item->item_type) {
                    'material' => $xaByType->get($normalize($item->product_type)),
                    'labour'   => $xaByLabour->get($normalize($item->labour_type)),
                    'freight'  => $xaByFreight->get($normalize($item->freight_description)),
                    default    => null,
                };

                if ($xa?->xa_description_override) {
                    $description = $xa->xa_description_override;
                }

                $sheet->setCellValue("A{$row}", $material);
                $sheet->setCellValue("B{$row}", $description);
                // Set as explicit strings so codes like "007" keep their leading zeros
                $sheet->setCellValueExplicit("C{$row}", (string) ($xa?->xa_cat ?? ''), DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$row}", (string) ($xa?->xa_selector ?? ''), DataType::TYPE_STRING);
                $sheet->setCellValue("E{$row}", $item->unit);
                $sheet->setCellValue("F{$row}", (float) $item->quantity);
                $sheet->setCellValue("G{$row}", (float) $item->sell_price);
                $sheet->setCellValue("H{$row}", (float) $item->line_total);
                $sheet->setCellValue("I{$row}", $action);

                $row++;
            }
        }

        $lastItemRow = $row - 1;
        // Skip when there are no items, otherwise the range would cover the header row
        if ($lastItemRow > $headerRow) {
            $sheet->getStyle('F' . ($headerRow + 1) . ":H{$lastItemRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // ── Totals block ────────────────────────────────────────────
        $row++; // blank separator row
        $taxLabel = $estimate->tax_group_id
            ? (DB::table('tax_rate_groups')->where('id', $estimate->tax_group_id)->value('name') ?: 'Tax')
            : 'Tax';

        $totals = [
            ['Subtotal', (float) $estimate->pretax_total],
            [$taxLabel . ' (' . rtrim(rtrim(number_format((float) $estimate->tax_rate_percent, 3), '0'), '.') . '%)', (float) $estimate->tax_amount],
            ['Total', (float) $estimate->grand_total],
        ];

        foreach ($totals as [$label, $value]) {
            $sheet->setCellValue("G{$row}", $label);
            $sheet->setCellValue("H{$row}", $value);
            $sheet->getStyle("G{$row}")->getFont()->setBold(true);
            $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            if ($label === 'Total') {
                $sheet->getStyle("G{$row}:H{$row}")->applyFromArray([
                    'font'   => ['bold' => true],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
            }
            $row++;
        }

        // ── Notes / Scope ───────────────────────────────────────────
        $row += 2;
        $sheet->setCellValue("A{$row}", 'Notes / Scope:');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue("A{$row}", $estimate->notes ?: '');
        $sheet->mergeCells("A{$row}:I" . ($row + 3));
        $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

        foreach (['A' => 32, 'B' => 48, 'C' => 10, 'D' => 12, 'E' => 8, 'F' => 10, 'G' => 12, 'H' => 12, 'I' => 8] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        return $spreadsheet;
    }

    /**
     * Returns [Material, Description, Action] for one estimate line item,
     * matching the Material/Description/Action columns in the reference
     * Xactimate-style export: "{Category} - Materials"/"- Install" for the
     * Material column, "M"/"I"/"F" for Action.
     */
    private function describeItem(\App\Models\EstimateItem $item): array
    {
        return match ($item->item_type) {
            'material' => [
                trim(($item->product_type ?: 'Material') . ' - Materials'),
                $item->description ?: trim(collect([$item->manufacturer, $item->style, $item->color_item_number])->filter()->implode(' ')),
                'M',
            ],
            'labour' => [
                trim(($item->labour_type ?: 'Labour') . ' - Install'),
                $item->description ?: '',
                'I',
            ],
            'freight' => [
                'Freight',
                $item->freight_description ?: '',
                'F',
            ],
            default => [$item->item_type, $item->description ?: '', ''],
        };
    }

    public function filename(Estimate $estimate): string
    {
        return 'Estimate-' . ($estimate->estimate_number ?? $estimate->id) . '.xlsx';
    }

    public function stream(Estimate $estimate): string
    {
        $spreadsheet = $this->build($estimate);
        $writer      = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }
}
