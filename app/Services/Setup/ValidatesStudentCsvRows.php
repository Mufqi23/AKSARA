<?php

namespace App\Services\Setup;

trait ValidatesStudentCsvRows
{
    /** Both legacy names and scoped descriptors are trusted setup targets, never CSV data. */
    private static function targetBlocks(array $targets, array &$errors): array
    {
        if (count($targets) > self::MAX_BLOCKS) {
            $errors[0][] = 'Configure at most 300 target blocks for one student import.';
            $targets = array_slice($targets, 0, self::MAX_BLOCKS, true);
        }
        $blocks = [];
        $scopes = [];
        $indices = [];
        $legacy = count(array_filter($targets, 'is_string')) === count($targets);
        $descriptors = count(array_filter($targets, 'is_array')) === count($targets);
        if (! $legacy && ! $descriptors) {
            $errors[0][] = 'Target blocks must use one consistent setup format.';

            return [];
        }
        foreach ($targets as $key => $target) {
            $index = $legacy ? (is_int($key) ? $key : count($blocks)) : ($target['index'] ?? $key);
            $name = $legacy ? trim($target) : (is_string($target['name'] ?? null) ? trim($target['name']) : '');
            if (filter_var($index, FILTER_VALIDATE_INT) === false || (int) $index < 0 || $name === '' || mb_strlen($name) > 120) {
                $errors[0][] = 'Configure a valid index and a non-empty block name of at most 120 characters for each target block.';

                continue;
            }
            $index = (int) $index;
            $program = $legacy ? '' : (is_string($target['program'] ?? null) ? trim($target['program']) : '');
            $programName = $legacy ? '' : (is_string($target['program_name'] ?? null) ? trim($target['program_name']) : '');
            $yearLevel = $legacy ? '' : (is_string($target['year_level'] ?? null) ? trim($target['year_level']) : '');
            $level = $legacy ? null : ($target['level'] ?? null);
            if (! $legacy && ($program === '' || $programName === '' || $yearLevel === '' || max(mb_strlen($program), mb_strlen($programName), mb_strlen($yearLevel)) > 120 || filter_var($level, FILTER_VALIDATE_INT) === false || (int) $level < 1 || (int) $level > 12)) {
                $errors[0][] = 'Each target block needs a valid Program and Year Level from this setup.';

                continue;
            }
            $scope = json_encode([self::key($program), $level === null ? null : (int) $level, self::key($name)]);
            if (isset($scopes[$scope])) {
                $errors[0][] = $legacy ? 'Target block names must be unique, ignoring letter case.' : 'Each target block must have a unique Program, Year Level, and Block name.';
            }
            if (isset($indices[$index])) {
                $errors[0][] = 'Target blocks must have unique setup indices.';
            }
            $scopes[$scope] = true;
            $indices[$index] = true;
            $label = $legacy ? $name : $program.' — '.$yearLevel.' — Block '.$name;
            if (! $legacy && is_string($target['label'] ?? null) && trim($target['label']) !== '') {
                $label = mb_substr(trim($target['label']), 0, 400);
            }
            $blocks[] = ['index' => $index, 'name' => $name, 'program' => $program, 'program_name' => $programName, 'year_level' => $yearLevel, 'level' => $level === null ? null : (int) $level, 'label' => $label];
        }
        if (! $blocks) {
            $errors[0][] = 'Configure at least one target block before importing students.';
        }

        return $blocks;
    }

    private static function resolveBlock(array &$row, array $blocks, array &$errors): void
    {
        if ($row['block'] === '') {
            return;
        }
        $byName = array_values(array_filter($blocks, fn ($block) => self::key($block['name']) === self::key($row['block'])));
        if (! $byName) {
            $errors[$row['row']][] = 'Block must match one of the blocks in this academic setup.';

            return;
        }
        $matches = array_values(array_filter($byName, function ($block) use ($row) {
            $program = self::key($row['program']);
            $yearLevel = self::key($row['year_level']);
            $programMatches = $program === '' || $program === self::key($block['program']) || $program === self::key($block['program_name']);
            $levelMatches = $yearLevel === '' || $yearLevel === self::key($block['year_level']) || ($block['level'] !== null && ctype_digit($yearLevel) && (int) $yearLevel === $block['level']);

            return $programMatches && $levelMatches;
        }));
        if (count($matches) === 1) {
            $row['block'] = $matches[0]['name'];
            $row['block_index'] = $matches[0]['index'];
            $row['block_label'] = $matches[0]['label'];

            return;
        }
        $choices = implode('; ', array_column(array_slice($matches ?: $byName, 0, 5), 'label'));
        $more = count($matches ?: $byName) > 5 ? '; additional choices are listed in the setup' : '';
        $errors[$row['row']][] = $matches
            ? 'Block is ambiguous. Add Program and Year Level columns to identify the correct target. Choices: '.$choices.$more.'.'
            : 'Program or Year Level does not match this Block in the academic setup. Choices: '.$choices.$more.'.';
    }

    private static function result(array $rows, array $errors): array
    {
        foreach ($rows as &$row) {
            $row['errors'] = array_values(array_unique($errors[$row['row']] ?? []));
            $row['valid'] = ! $row['errors'];
        }
        unset($row);
        $errors = array_map(fn ($messages) => array_values(array_unique($messages)), $errors);
        $valid = count(array_filter($rows, fn ($row) => $row['valid']));

        return ['rows' => $rows, 'errors' => $errors, 'valid_count' => $valid, 'invalid_count' => count($rows) - $valid];
    }

    private static function key(string $value): string
    {
        return mb_strtolower($value);
    }

    private static function wellFormedCsv(string $content): bool
    {
        $content = preg_replace('/^\x{FEFF}/u', '', $content);
        $quoted = false;
        $afterQuote = false;
        $fieldStart = true;
        $length = strlen($content);
        for ($i = 0; $i < $length; $i++) {
            $char = $content[$i];
            if ($quoted) {
                if ($char === '"') {
                    if ($i + 1 < $length && $content[$i + 1] === '"') {
                        $i++;
                    } else {
                        $quoted = false;
                        $afterQuote = true;
                    }
                }

                continue;
            }
            if ($char === ',' || $char === "\r" || $char === "\n") {
                $afterQuote = false;
                $fieldStart = true;
            } elseif ($afterQuote) {
                return false;
            } elseif ($char === '"') {
                if (! $fieldStart) {
                    return false;
                }
                $quoted = true;
                $fieldStart = false;
            } else {
                $fieldStart = false;
            }
        }

        return ! $quoted;
    }
}

