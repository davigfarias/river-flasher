<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ParsedCsv;
use App\Enums\Language;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class ParseCsvCards
{
    private const array COLUMN_MAP = [
        'word' => 'word',
        'palavra' => 'word',
        'definition' => 'definition',
        'definicao' => 'definition',
        'significado' => 'definition',
        'language' => 'language',
        'idioma' => 'language',
        'pos' => 'pos',
        'classe gramatical' => 'pos',
        'parte de fala' => 'pos',
        'category' => 'category',
        'categoria' => 'category',
        'transliteration' => 'transliteration',
        'transliteracao' => 'transliteration',
        'example' => 'example',
        'exemplo' => 'example',
        'translation' => 'translation',
        'traducao' => 'translation',
        'is_difficult' => 'is_difficult',
        'dificil' => 'is_difficult',
    ];

    private const array REQUIRED_FIELDS = ['word', 'definition'];

    private const array TRUTHY_VALUES = ['true', '1', 'sim', 'yes'];

    private const array LANGUAGE_VALUES = [
        'el' => Language::Greek,
        'grego' => Language::Greek,
        'greek' => Language::Greek,
        'he' => Language::Hebrew,
        'hebraico' => Language::Hebrew,
        'hebrew' => Language::Hebrew,
    ];

    public function handle(string $csvContent): ParsedCsv
    {
        $csvContent = $this->normalizeEncoding($csvContent);
        $csvContent = ltrim($csvContent, "\xEF\xBB\xBF");

        $stream = fopen('php://temp', 'r+');

        throw_if($stream === false, new RuntimeException('Failed to open temporary stream for CSV parsing.'));

        fwrite($stream, $csvContent);
        rewind($stream);

        $header = fgetcsv($stream, 0, ',', '"', '\\');

        if ($header === false || $header === [null]) {
            fclose($stream);

            return new ParsedCsv(rows: [], rowErrors: [], missingColumns: self::REQUIRED_FIELDS, languagesMixed: false, detectedLanguage: null);
        }

        $fieldByColumn = [];

        foreach ($header as $index => $title) {
            $normalized = Str::of((string) $title)->trim()->lower()->ascii()->toString();
            $fieldByColumn[$index] = self::COLUMN_MAP[$normalized] ?? null;
        }

        $presentFields = array_filter(array_unique(array_values($fieldByColumn)));
        $missingColumns = array_values(array_diff(self::REQUIRED_FIELDS, $presentFields));

        if ($missingColumns !== []) {
            fclose($stream);

            return new ParsedCsv(rows: [], rowErrors: [], missingColumns: $missingColumns, languagesMixed: false, detectedLanguage: null);
        }

        $rows = [];
        $rowErrors = [];
        $languagesSeen = [];
        $line = 1;

        while (($record = fgetcsv($stream, 0, ',', '"', '\\')) !== false) {
            $line++;

            if ($record === [null]) {
                continue;
            }

            $fields = [];

            foreach ($fieldByColumn as $index => $field) {
                if ($field !== null) {
                    $fields[$field] = trim((string) ($record[$index] ?? ''));
                }
            }

            $word = $fields['word'] ?? '';
            $definition = $fields['definition'] ?? '';

            if ($word === '' || $definition === '') {
                $rowErrors[$line] = 'campos obrigatórios (palavra/definição) faltando';

                continue;
            }

            $language = null;

            if (($fields['language'] ?? '') !== '') {
                $language = self::LANGUAGE_VALUES[Str::of($fields['language'])->trim()->lower()->ascii()->toString()] ?? null;

                if ($language === null) {
                    $rowErrors[$line] = 'idioma "'.$fields['language'].'" não reconhecido';

                    continue;
                }

                $languagesSeen[$language->value] = true;
            }

            $rows[] = [
                'word' => $word,
                'definition' => $definition,
                'transliteration' => ($fields['transliteration'] ?? '') !== '' ? $fields['transliteration'] : null,
                'example' => ($fields['example'] ?? '') !== '' ? $fields['example'] : null,
                'translation' => ($fields['translation'] ?? '') !== '' ? $fields['translation'] : null,
                'pos' => ($fields['pos'] ?? '') !== '' ? $fields['pos'] : null,
                'category' => ($fields['category'] ?? '') !== '' ? $fields['category'] : null,
                'isDifficult' => in_array(Str::of($fields['is_difficult'] ?? '')->trim()->lower()->toString(), self::TRUTHY_VALUES, true),
            ];
        }

        fclose($stream);

        $languagesMixed = count($languagesSeen) > 1;
        $detectedLanguage = ! $languagesMixed && $languagesSeen !== [] ? Language::from(array_key_first($languagesSeen)) : null;

        return new ParsedCsv(
            rows: $rows,
            rowErrors: $rowErrors,
            missingColumns: [],
            languagesMixed: $languagesMixed,
            detectedLanguage: $detectedLanguage,
        );
    }

    /**
     * Uploaded CSVs may arrive as Latin-1 (common export from spreadsheet
     * tools in pt-BR locales) — detect and normalize to UTF-8 so accented
     * headers/words don't get mangled downstream.
     */
    private function normalizeEncoding(string $content): string
    {
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        if ($encoding !== false && $encoding !== 'UTF-8') {
            $converted = mb_convert_encoding($content, 'UTF-8', $encoding);

            return $converted !== false ? $converted : $content;
        }

        return $content;
    }
}
