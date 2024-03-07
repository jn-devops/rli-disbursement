<?php

namespace App\Nova;

use Laravel\Nova\Fields\{Boolean, Currency, DateTime, ID, MorphTo, Text};
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Actions\ExportAsCsv;

class Wallet extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\Bavix\Wallet\Models\Wallet>
     */
    public static $model = \Bavix\Wallet\Models\Wallet::class;

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
            MorphTo::make('Holder'),
            Text::make('Name')->sortable(),
            Currency::make('Balance')->asMinorUnits()->currency('PHP')->sortable(),
            DateTime::make('Created', 'created_at')->withFriendlyDate()->sortable(),
            DateTime::make('Updated', 'updated_at')->withFriendlyDate()->sortable(),
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
        return [
            ExportAsCsv::make()->nameable(),
        ];
    }
}
