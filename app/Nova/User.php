<?php

namespace App\Nova;

use App\Nova\Actions\{DisburseCredits, TransferCredits};
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\MorphMany;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\MorphOne;
use Laravel\Nova\Fields\HasMany;
use Illuminate\Validation\Rules;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\ID;

class User extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\User>
     */
    public static $model = \App\Models\User::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The visual style used for the table. Available options are 'tight' and 'default'.
     *
     * @var string
     */
    public static $tableStyle = 'tight';

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = true;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'email', 'mobile', 'meta->merchant->name', 'meta->merchant->city',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Gravatar::make()->maxWidth(50),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Email')
                ->sortable()
                ->rules('required', 'email', 'max:254')
                ->creationRules('unique:users,email')
                ->updateRules('unique:users,email,{{resourceId}}'),

            Text::make('Mobile')
                ->sortable()
                ->rules('required', 'max:11')
                ->creationRules('unique:users,mobile')
                ->updateRules('unique:users,mobile,{{resourceId}}'),

            Text::make('Merchant Code')
                ->hideFromIndex()
                ->hideWhenUpdating()
                ->sortable(),

            Text::make('Merchant Name')
                ->sortable(),

            Text::make('Merchant City')
                ->sortable(),

            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', Rules\Password::defaults())
                ->updateRules('nullable', Rules\Password::defaults()),

            MorphOne::make('Wallet')
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            MorphMany::make('Transactions')
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            HasMany::make('Transfers')
                ->hideWhenCreating()
                ->hideWhenUpdating(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function cards(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function filters(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function actions(NovaRequest $request): array
    {
        return [
            (new TransferCredits)
                ->confirmText('Are you sure you want to transfer credits?')
                ->confirmButtonText('Transfer')
                ->cancelButtonText("Don't transfer"),
            (new DisburseCredits())
                ->confirmText('Are you sure you want to disburse credits?')
                ->confirmButtonText('Disburse')
                ->cancelButtonText("Don't disburse"),
        ];
    }
}
