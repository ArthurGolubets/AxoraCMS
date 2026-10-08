<?php

namespace HolartWeb\AxoraCMS\Support;

use Illuminate\Contracts\Database\Query\Builder;

/**
 * Word-based LIKE search: the query is split into words (quotes and similar
 * punctuation are ignored) and every word must occur in at least one of the
 * given columns, in any order. So «Фасад POIN» finds «Фасад "POINT" белый».
 */
class SearchTerms
{
    /**
     * Characters that separate words. Quotes/brackets are dropped entirely;
     * "-", ".", "/", "_" are kept because they are common inside SKUs.
     */
    private const SEPARATORS = '/[\s"\'`«»„“”‘’,;:!?()\[\]{}]+/u';

    /**
     * Split a raw search query into normalized words.
     *
     * @return array<int, string>
     */
    public static function words(?string $search): array
    {
        $parts = preg_split(self::SEPARATORS, trim((string) $search), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique($parts ?: []));
    }

    /**
     * Constrain the query so that every word matches at least one column.
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @param  array<int, string>  $columns
     * @return TBuilder
     */
    public static function apply(Builder $query, ?string $search, array $columns): Builder
    {
        foreach (self::words($search) as $word) {
            $pattern = '%'.$word.'%';

            $query->where(function ($wordQuery) use ($columns, $pattern) {
                foreach ($columns as $column) {
                    $wordQuery->orWhere($column, 'like', $pattern);
                }
            });
        }

        return $query;
    }
}
