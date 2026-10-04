<?php

namespace HolartWeb\AxoraCMS\Services\ImportExport;

use DOMDocument;
use DOMElement;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Reads import files (xlsx / xls / csv / xml) into rows keyed by column key.
 *
 * Spreadsheets and CSV: the first row is the header. XML: the most repeated
 * element is treated as a row; its child elements, attributes ("@attr") and
 * YML-style <param name="..."> become columns.
 */
class ImportFileReader
{
    public const SAMPLE_ROWS = 10;

    /**
     * Columns, a few sample rows and the row count.
     *
     * @return array{columns: array<int, array{key: string, label: string}>, sample: array<int, array<string, string>>, total: int}
     */
    public function inspect(string $path, string $format): array
    {
        [$columns, $rows] = $this->read($path, $format);

        return [
            'columns' => $columns,
            'sample' => array_slice($rows, 0, self::SAMPLE_ROWS),
            'total' => count($rows),
        ];
    }

    /**
     * Convert the file into JSON Lines (one row per line) for chunked processing.
     */
    public function toJsonLines(string $path, string $format, string $target): int
    {
        [, $rows] = $this->read($path, $format);

        $handle = fopen($target, 'w');
        if (! $handle) {
            throw new RuntimeException('Не удалось подготовить файл импорта');
        }

        foreach ($rows as $row) {
            fwrite($handle, json_encode($row, JSON_UNESCAPED_UNICODE)."\n");
        }
        fclose($handle);

        return count($rows);
    }

    /**
     * Rows [$offset, $offset + $limit) of a JSON Lines file.
     *
     * @return array<int, array<string, string>> Row number (1-based, data rows) => row.
     */
    public function readJsonLines(string $path, int $offset, int $limit): array
    {
        $file = new \SplFileObject($path);
        $file->seek($offset);

        $rows = [];
        while (! $file->eof() && count($rows) < $limit) {
            $line = trim((string) $file->current());
            if ($line !== '') {
                $rows[$offset + count($rows) + 1] = json_decode($line, true) ?: [];
            }
            $file->next();
        }

        return $rows;
    }

    /**
     * @return array{0: array<int, array{key: string, label: string}>, 1: array<int, array<string, string>>}
     */
    protected function read(string $path, string $format): array
    {
        return match ($format) {
            'xml' => $this->readXml($path),
            'csv' => $this->tableToRows($this->readCsv($path)),
            'xlsx', 'xls' => $this->tableToRows($this->readSpreadsheet($path)),
            default => throw new RuntimeException('Неподдерживаемый формат файла'),
        };
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function readSpreadsheet(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        return $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);
    }

    /**
     * @return array<int, array<int, string>>
     */
    protected function readCsv(string $path): array
    {
        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1251');
        }

        $firstLine = strtok($content, "\n") ?: '';
        $delimiter = collect([';', ',', "\t", '|'])
            ->sortByDesc(fn ($candidate) => substr_count($firstLine, $candidate))
            ->first();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $table = [];
        while (($row = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            if ($row !== [null]) {
                $table[] = $row;
            }
        }
        fclose($stream);

        return $table;
    }

    /**
     * First row → columns, the rest → rows keyed "c0", "c1", ...
     *
     * @param  array<int, array<int, mixed>>  $table
     * @return array{0: array<int, array{key: string, label: string}>, 1: array<int, array<string, string>>}
     */
    protected function tableToRows(array $table): array
    {
        $header = array_shift($table) ?? [];
        $width = max(count($header), ...array_map('count', $table ?: [[]]));

        $columns = [];
        for ($i = 0; $i < $width; $i++) {
            $label = trim((string) ($header[$i] ?? ''));
            $columns[] = ['key' => 'c'.$i, 'label' => $label !== '' ? $label : 'Колонка '.($i + 1)];
        }

        $rows = [];
        foreach ($table as $line) {
            $row = [];
            $hasValue = false;
            for ($i = 0; $i < $width; $i++) {
                $value = $line[$i] ?? '';
                $value = is_scalar($value) ? trim((string) $value) : '';
                $hasValue = $hasValue || $value !== '';
                $row['c'.$i] = $value;
            }
            if ($hasValue) {
                $rows[] = $row;
            }
        }

        return [$columns, $rows];
    }

    /**
     * Suffix of columns with resolved references ("categoryId" → "categoryId__path").
     */
    public const PATH_SUFFIX = '__path';

    /** Attributes / child tags that identify a dictionary entry. */
    protected const ID_NAMES = ['id', 'Id', 'ID', 'Ид', 'code', 'Code', 'Код', 'uid', 'guid'];

    /** Child tags with the entry name (for entries that are not plain text). */
    protected const NAME_NAMES = ['name', 'Name', 'Наименование', 'Название', 'title', 'Title', 'caption'];

    /** Attributes / child tags pointing to the parent entry. */
    protected const PARENT_NAMES = ['parentId', 'ParentId', 'parentid', 'parent_id', 'parent', 'Parent', 'Родитель'];

    /** Dictionaries whose names look like a category tree are suggested for the "Категория" field. */
    protected const CATEGORY_PATTERN = '/categor|group|групп|раздел|section|каталог|catalog/iu';

    /**
     * @return array{0: array<int, array{key: string, label: string, suggest?: string}>, 1: array<int, array<string, string>>}
     */
    protected function readXml(string $path): array
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        // LIBXML_NONET: never fetch external resources while parsing.
        $loaded = $dom->load($path, LIBXML_NONET | LIBXML_COMPACT);
        libxml_use_internal_errors($previous);

        if (! $loaded || ! $dom->documentElement) {
            throw new RuntimeException('Не удалось прочитать XML-файл');
        }

        $items = $this->repeatingElements($dom);

        $columns = [];
        $rows = [];
        foreach ($items as $item) {
            $row = [];
            foreach ($item->attributes ?? [] as $attribute) {
                $row['@'.$attribute->name] = $this->text($attribute->value);
            }
            foreach ($item->childNodes as $child) {
                if (! $child instanceof DOMElement) {
                    continue;
                }
                $key = $child->tagName === 'param' && $child->hasAttribute('name')
                    ? 'param:'.$child->getAttribute('name')
                    : $child->tagName;
                $value = $this->leafValues($child);
                // Repeated tags (e.g. several <picture>) are joined.
                $row[$key] = isset($row[$key]) && $row[$key] !== '' ? $row[$key].'; '.$value : $value;
            }

            foreach (array_keys($row) as $key) {
                $columns[$key] ??= ['key' => $key, 'label' => str_starts_with($key, 'param:') ? substr($key, 6) : $key];
            }
            $rows[] = $row;
        }

        $rows = array_map(fn ($row) => array_merge(array_fill_keys(array_keys($columns), ''), $row), $rows);

        [$columns, $rows] = $this->resolveReferences($dom, $items, $columns, $rows);

        return [array_values($columns), $rows];
    }

    /**
     * Text of an element; for nested elements — the texts of its leaves joined
     * with "; " (e.g. <Группы><Ид>1</Ид><Ид>2</Ид></Группы> → "1; 2").
     */
    protected function leafValues(DOMElement $element): string
    {
        if (! $this->hasChildElements($element)) {
            return $this->text($element->textContent);
        }

        $values = [];
        foreach ($element->getElementsByTagName('*') as $descendant) {
            if (! $this->hasChildElements($descendant)) {
                $text = $this->text($descendant->textContent);
                if ($text !== '') {
                    $values[] = $text;
                }
            }
        }

        return implode('; ', $values);
    }

    /**
     * Find dictionaries (categories, groups, vendors, ...) anywhere in the file and,
     * for every column that references one of them, add a "{column}__path" column
     * with the referenced names ("Parent / Child" for trees).
     *
     * @param  array<int, DOMElement>  $items
     * @param  array<string, array{key: string, label: string}>  $columns
     * @param  array<int, array<string, string>>  $rows
     * @return array{0: array<string, array<string, string>>, 1: array<int, array<string, string>>}
     */
    protected function resolveReferences(DOMDocument $dom, array $items, array $columns, array $rows): array
    {
        $dictionaries = $this->dictionaries($dom, $items);
        if (! $dictionaries) {
            return [$columns, $rows];
        }

        foreach (array_keys($columns) as $key) {
            // The record's own identifier is not a reference.
            if (in_array(ltrim($key, '@'), self::ID_NAMES, true)) {
                continue;
            }

            $values = [];
            foreach ($rows as $row) {
                foreach (preg_split('/\s*;\s*/u', (string) $row[$key], -1, PREG_SPLIT_NO_EMPTY) as $value) {
                    $values[] = $value;
                }
            }
            if (! $values) {
                continue;
            }

            $best = null;
            $bestMatches = 0;
            foreach ($dictionaries as $tag => $dictionary) {
                $matches = count(array_filter($values, fn ($value) => isset($dictionary['paths'][$value])));
                if ($matches > $bestMatches && $matches / count($values) >= 0.8) {
                    [$best, $bestMatches] = [$tag, $matches];
                }
            }
            if (! $best) {
                continue;
            }

            $dictionary = $dictionaries[$best];
            $pathKey = $key.self::PATH_SUFFIX;
            $columns[$pathKey] = array_filter([
                'key' => $pathKey,
                'label' => $columns[$key]['label'].' → '.$best.($dictionary['tree'] ? ' (путь)' : ' (название)'),
                'suggest' => preg_match(self::CATEGORY_PATTERN, $best.' '.$key) ? 'category' : null,
            ]);

            foreach ($rows as &$row) {
                $refs = preg_split('/\s*;\s*/u', (string) $row[$key], -1, PREG_SPLIT_NO_EMPTY);
                $row[$pathKey] = implode('; ', array_filter(array_map(fn ($ref) => $dictionary['paths'][$ref] ?? '', $refs)));
            }
            unset($row);
        }

        return [$columns, $rows];
    }

    /**
     * Repeated entries with an identifier and a name outside the data rows,
     * grouped by tag: [tag => ['paths' => [id => "Parent / Child"], 'tree' => bool]].
     *
     * Parents come from a parent attribute / child tag or from nesting of the same tag.
     *
     * @param  array<int, DOMElement>  $items
     * @return array<string, array{paths: array<string, string>, tree: bool}>
     */
    protected function dictionaries(DOMDocument $dom, array $items): array
    {
        $records = new \SplObjectStorage;
        foreach ($items as $item) {
            $records->attach($item);
        }

        $entries = [];
        foreach ($dom->getElementsByTagName('*') as $element) {
            if ($this->insideRecords($element, $records)) {
                continue;
            }

            $id = $this->firstValue($element, self::ID_NAMES);
            $name = $this->hasChildElements($element)
                ? $this->firstValue($element, self::NAME_NAMES, attributes: false)
                : $this->text($element->textContent);

            if ($id === null || $name === null || $name === '') {
                continue;
            }

            $parent = $this->firstValue($element, self::PARENT_NAMES);
            if ($parent === null) {
                // Nested trees: <Группа><Группы><Группа>...</Группа></Группы></Группа>.
                for ($ancestor = $element->parentNode; $ancestor instanceof DOMElement; $ancestor = $ancestor->parentNode) {
                    if ($ancestor->tagName === $element->tagName) {
                        $parent = $this->firstValue($ancestor, self::ID_NAMES);
                        break;
                    }
                }
            }

            $entries[$element->tagName][$id] = ['name' => $name, 'parent' => $parent];
        }

        $dictionaries = [];
        foreach ($entries as $tag => $list) {
            $paths = [];
            $tree = false;
            foreach (array_keys($list) as $id) {
                $parts = [];
                $current = (string) $id;
                $guard = 0;
                while (isset($list[$current]) && $guard++ < 50) {
                    array_unshift($parts, $list[$current]['name']);
                    $current = (string) $list[$current]['parent'];
                }
                $tree = $tree || count($parts) > 1;
                $paths[(string) $id] = implode(' / ', $parts);
            }
            $dictionaries[$tag] = ['paths' => $paths, 'tree' => $tree];
        }

        return $dictionaries;
    }

    protected function insideRecords(DOMElement $element, \SplObjectStorage $records): bool
    {
        for ($node = $element; $node instanceof DOMElement; $node = $node->parentNode) {
            if ($records->contains($node)) {
                return true;
            }
        }

        return false;
    }

    /**
     * First non-empty value among the given attributes or direct leaf children.
     *
     * @param  array<int, string>  $names
     */
    protected function firstValue(DOMElement $element, array $names, bool $attributes = true): ?string
    {
        if ($attributes) {
            foreach ($names as $name) {
                if ($element->hasAttribute($name) && trim($element->getAttribute($name)) !== '') {
                    return trim($element->getAttribute($name));
                }
            }
        }

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && in_array($child->tagName, $names, true) && ! $this->hasChildElements($child)) {
                $value = $this->text($child->textContent);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    protected function hasChildElements(DOMElement $element): bool
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clean XML text: decode HTML entities (feeds often double-encode them)
     * and drop the indentation of multi-line values.
     */
    protected function text(string $value): string
    {
        $value = html_entity_decode(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace("\u{00A0}", ' ', $value);
        $lines = array_map('trim', preg_split('/\R/u', $value) ?: []);

        return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));
    }

    /**
     * The data rows: elements that repeat under a common parent with the most
     * "count × fields" (offers, products, items...).
     *
     * @return array<int, DOMElement>
     */
    protected function repeatingElements(DOMDocument $dom): array
    {
        $best = [];
        $bestScore = 0;
        $walk = function (DOMElement $parent) use (&$walk, &$best, &$bestScore) {
            $groups = [];
            foreach ($parent->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    $groups[$child->tagName][] = $child;
                }
            }
            foreach ($groups as $elements) {
                // Data rows are the most numerous *and* richest entries: a list of
                // 200 categories (plain text) loses to 150 offers with 10 fields each.
                $fields = $elements[0]->attributes->length;
                foreach ($elements[0]->childNodes as $child) {
                    $fields += $child instanceof DOMElement ? 1 : 0;
                }
                $score = count($elements) * max(1, $fields);
                if ($fields > 0 && $score > $bestScore) {
                    [$best, $bestScore] = [$elements, $score];
                }
                foreach ($elements as $element) {
                    $walk($element);
                }
            }
        };
        $walk($dom->documentElement);

        return $best;
    }
}
