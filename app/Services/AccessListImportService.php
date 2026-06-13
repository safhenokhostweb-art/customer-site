<?php

namespace App\Services;

use App\Models\AccessList;
use App\Models\AccessListEntry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class AccessListImportService
{
    private const NATIONAL_ID_HEADERS = ['کد ملی', 'کدملی', 'national_id', 'national id', 'nationalid', 'ملی'];
    private const LAST_NAME_HEADERS   = ['نام خانوادگی', 'نام‌خانوادگی', 'last_name', 'last name', 'lastname', 'خانوادگی', 'فامیل'];

    /**
     * Import entries from an uploaded xlsx/xls/csv file into the given AccessList.
     * Returns ['inserted' => int, 'skipped' => int, 'errors' => string[]].
     */
    public function import(AccessList $list, UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true); // assoc by col letter

        if (empty($rows)) {
            return ['inserted' => 0, 'skipped' => 0, 'errors' => ['فایل خالی است']];
        }

        // Detect header row (first row)
        $headers   = array_map(fn($v) => mb_strtolower(trim((string) $v)), $rows[1] ?? []);
        $colMap    = $this->detectColumns($headers, $list->match_type);

        if (empty($colMap)) {
            return ['inserted' => 0, 'skipped' => 0, 'errors' => ['ستون‌های مورد نیاز در فایل یافت نشد']];
        }

        $inserted = 0;
        $skipped  = 0;
        $errors   = [];
        $batch    = [];

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex === 1) continue; // skip header

            $nationalId = null;
            $lastName   = null;

            if (isset($colMap['national_id'])) {
                $nationalId = trim((string) ($row[$colMap['national_id']] ?? ''));
                $nationalId = preg_replace('/\D/', '', $nationalId); // digits only
                if ($nationalId === '') $nationalId = null;
            }

            if (isset($colMap['last_name'])) {
                $lastName = mb_strtolower(trim((string) ($row[$colMap['last_name']] ?? '')));
                if ($lastName === '') $lastName = null;
            }

            // Validate required fields per match_type
            $valid = match($list->match_type) {
                'national_id' => $nationalId !== null,
                'last_name'   => $lastName !== null,
                'both'        => $nationalId !== null && $lastName !== null,
                default       => false,
            };

            if (!$valid) {
                $skipped++;
                continue;
            }

            $batch[] = [
                'access_list_id' => $list->id,
                'national_id'    => $nationalId,
                'last_name'      => $lastName,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            // Flush in chunks
            if (count($batch) >= 500) {
                DB::table('access_list_entries')->insert($batch);
                $inserted += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('access_list_entries')->insert($batch);
            $inserted += count($batch);
        }

        // Update count
        $list->update(['entry_count' => AccessListEntry::where('access_list_id', $list->id)->count()]);

        return compact('inserted', 'skipped', 'errors');
    }

    /**
     * Parse header row and return ['national_id' => 'A', 'last_name' => 'B'] (col letters).
     */
    private function detectColumns(array $headers, string $matchType): array
    {
        $colMap = [];
        foreach ($headers as $colLetter => $header) {
            if (in_array($header, self::NATIONAL_ID_HEADERS)) {
                $colMap['national_id'] = $colLetter;
            } elseif (in_array($header, self::LAST_NAME_HEADERS)) {
                $colMap['last_name'] = $colLetter;
            }
        }
        return $colMap;
    }
}
