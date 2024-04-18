<?php

namespace App\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Text;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Naoray\NovaJson\JSON;
use Laravel\Nova\Panel;



class Reference extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Reference>
     */
    public static $model = \App\Models\Reference::class;

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
        'id', 'code', 'operation_id'
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
            Text::make('Reference', 'code')->sortable(),
            Text::make('Transaction Id', 'operation_id')->sortable(),
            BelongsTo::make('Merchant','user',User::class)
                ->displayUsing(fn () => $this->user->merchant_name),
            Text::make('Status', 'status->data->status'),
            DateTime::make('Updated', 'updated_at')->sortable(),
            new Panel('Inputs', [
                JSON::make('Inputs', [
                    Currency::make('Amount')->currency('PHP'),
                    Text::make('Via')->hideFromIndex(),
                    Text::make('Bank')->hideFromIndex(),
                    Text::make('Account Number')->hideFromIndex(),
                ]),
            ]),
            new Panel('JSON', [
                Code::make('Request')->json(),
                Code::make('Response')->json(),
                Code::make('Status')->json()
            ]),

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
