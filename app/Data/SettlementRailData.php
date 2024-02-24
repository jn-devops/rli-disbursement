<?php

namespace App\Data;

use App\Collections\SettlementRailCollection;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\CursorPaginatedDataCollection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\PaginatedDataCollection;

class SettlementRailData extends Data
{
    public function __construct(
        #[MapInputName('bank_code')]
        public string $code,
        public string $name
    ) {}

//    public static function collect(mixed $items, ?string $into = null): array|DataCollection|PaginatedDataCollection|CursorPaginatedDataCollection|Enumerable|AbstractPaginator|PaginatorContract|AbstractCursorPaginator|CursorPaginatorContract|LazyCollection|Collection
//    {
//        foreach ($items as &$item) {
//            $item['code'] = $item['bank_code'];
//            unset($item['bank_code']);
//        }
//
//        return parent::collect($items);
//    }
}
