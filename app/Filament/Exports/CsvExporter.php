<?php

namespace App\Filament\Exports;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

abstract class CsvExporter
{
    /**
     * Available columns keyed by field name.
     *
     * @return array<string, string>
     */
    abstract public static function columns(): array;

    /**
     * Map a single record to a flat array keyed by selected column.
     *
     * @param  array<string>  $selected
     * @return array<string, mixed>
     */
    abstract protected function mapRow(object $record, array $selected): array;

    /**
     * Build the base query for export.
     */
    abstract protected function query(): Builder;

    /**
     * Generate a CSV file on the public disk and return its download URL.
     *
     * @param  array<string>  $selected
     * @return string Public URL to the generated CSV file.
     */
    public function store(string $filename, array $selected = []): string
    {
        $columns = static::columns();
        $selected = empty($selected)
            ? array_keys($columns)
            : array_values(array_intersect(array_keys($columns), $selected));

        $headers = array_map(fn($key) => $columns[$key], $selected);

        $query = $this->query();

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, $headers, escape: '');

        $query->chunk(1000, function ($records) use ($handle, $selected): void {
            foreach ($records as $record) {
                $row = $this->mapRow($record, $selected);

                $values = [];
                foreach ($selected as $key) {
                    $value = $row[$key] ?? null;
                    $values[] = $value instanceof DateTimeInterface
                        ? $value->format('Y-m-d H:i:s')
                        : ($value instanceof BackedEnum ? $value->value : (string) ($value ?? ''));
                }

                fputcsv($handle, $values, escape: '');
            }
        });

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('public')->put("exports/{$filename}", $content);

        return Storage::disk('public')->url("exports/{$filename}");
    }
}
