<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Departure;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OperationalCodeGenerator
{
    /**
     * @return array{column: string, value: string}
     */
    public function generate(string $resource, array $data): array
    {
        $branch = Branch::query()->findOrFail($data['branch_id']);
        $branchCode = trim(preg_replace(
            '/[^A-Z0-9]+/',
            '-',
            Str::upper(Str::ascii($branch->code)),
        ), '-');

        [$table, $column, $segment, $digits, $year] = match ($resource) {
            'pilgrims' => ['pilgrims', 'registration_number', 'JMH', 5, null],
            'tour-leaders' => ['tour_leaders', 'employee_number', 'TL', 3, null],
            'muthawwifs' => ['muthawwifs', 'employee_number', 'MTF', 3, null],
            'departures' => [
                'departures',
                'code',
                'DEP',
                3,
                Carbon::parse($data['departure_date'])->year,
            ],
            'groups' => [
                'groups',
                'code',
                'GRP',
                3,
                Departure::query()->findOrFail($data['departure_id'])->departure_date->year,
            ],
            default => throw new \InvalidArgumentException("Resource {$resource} tidak memiliki kode otomatis."),
        };

        $base = collect([$branchCode, $segment, $year])
            ->filter(fn ($part) => filled($part))
            ->implode('-');

        $existingCodes = DB::table($table)
            ->where($column, 'like', "{$base}-%")
            ->lockForUpdate()
            ->pluck($column);

        $pattern = '/^'.preg_quote($base, '/').'-(\d+)$/';
        $lastSequence = $existingCodes
            ->map(function (string $code) use ($pattern): int {
                return preg_match($pattern, $code, $matches)
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() ?? 0;

        return [
            'column' => $column,
            'value' => sprintf("%s-%0{$digits}d", $base, $lastSequence + 1),
        ];
    }
}
