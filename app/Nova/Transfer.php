<?php

namespace App\Nova;

use Laravel\Nova\Fields\{BelongsTo, Boolean, Currency, DateTime, ID, MorphTo, Text};
use Laravel\Nova\Http\Requests\NovaRequest;
use Brick\Money\Money;

class Transfer extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\Bavix\Wallet\Models\Transfer>
     */
    public static $model = \Bavix\Wallet\Models\Transfer::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('From', 'from', Wallet::class)
                ->displayUsing(fn () => "{$this->from->holder->name} {$this->from->name}"),
            BelongsTo::make('Withdraw', 'withdraw', Transaction::class)
                ->displayUsing(fn () => Money::ofMinor($this->withdraw->amount, 'PHP')->formatTo('en_US')),
            Text::make('Status')->sortable(),
            BelongsTo::make('To', 'to', Wallet::class)
                ->displayUsing(fn () => "{$this->to->holder->name} {$this->to->name}"),
            BelongsTo::make('Deposit', 'deposit', Transaction::class)
                ->displayUsing(fn () => Money::ofMinor($this->deposit->amount, 'PHP')->formatTo('en_US')),
            Currency::make('Discount')->asMinorUnits()->currency('PHP')->sortable(),
            Currency::make('Fee')->asMinorUnits()->currency('PHP')->sortable(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}