<?php

namespace App\Nova\Actions;

use Laravel\Nova\Actions\DestructiveAction;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Actions\RequestDisbursementAction;
use Illuminate\Support\Facades\Validator;
use Laravel\Nova\Fields\ActionFields;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\Text;
use Illuminate\Http\Request;
use Brick\Money\Money;
use App\Models\User;


class DisburseCredits extends DestructiveAction
{
    /**
     *
     */
    public function __construct()
    {
        $this->runCallback = function (Request $request, $model) {
            return $model instanceof User && $model->is($request->user());
        };
    }

    public function handle(ActionFields $fields, Collection $models)
    {
        tap(app( RequestDisbursementAction::class), function (RequestDisbursementAction $action) use ($fields, $models) {
            if($validated = Validator::make($fields->toArray(), $action->rules())->validate()) {
                $user = $models->first();
                $response = $action->run($user, $validated);
                logger($response);
            }

        });
    }

    public function fields(NovaRequest $request)
    {
        $min = Money::of(config('disbursement.min'), 'PHP');
        $max = Money::of(config('disbursement.max'), 'PHP');
        $inc = Money::of(10, 'PHP');

        return [
            Currency::make('Amount')
                ->min($min->getAmount()->toInt())->max($max->getAmount()->toInt())->step($inc->getAmount()->toInt())//TODO: put this in config
                ->default($min->getAmount()->toInt())
                ->help('min of ' . $min->formatTo('en_US') . ' to max of ' . $max->formatTo('en_US') . ' in increments of ' . $inc->formatTo('en_US')),
            Text::make('Bank')->default('GXCHPHM2XXX'),
            Text::make('Account Number')->default('09261816877'),
            Text::make('Via')->default('INSTAPAY'),
            Text::make('Reference')->default('CCC')
        ];
    }
}
