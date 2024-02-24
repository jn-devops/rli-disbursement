<?php

namespace App\Data;

use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\CursorPaginatedDataCollection;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\PaginatedDataCollection;

class BankData extends Data
{
    public function __construct(
        #[MapInputName('swift_bic')]
        public string $code,
        #[MapInputName('full_name')]
        public string $name,
        /** @var SettlementRailData[] */
        public DataCollection $settlement_rail,
    ) {}

    public static function collect(mixed $items, ?string $into = null): array|DataCollection|PaginatedDataCollection|CursorPaginatedDataCollection|Enumerable|AbstractPaginator|PaginatorContract|AbstractCursorPaginator|CursorPaginatorContract|LazyCollection|Collection
    {
        $items = $items['banks'];

        return parent::collect($items, $into);
    }

    public static function collectFromJsonFile(string $json_file): array
    {
        $json_path = documents_path($json_file);
        $array = json_decode(file_get_contents($json_path), true);

        return static::collect($array);
    }
}
