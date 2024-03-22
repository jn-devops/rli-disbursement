<?php

namespace App\Nova\Actions;

use Brick\Money\Money;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Nova\Actions\DestructiveAction;
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Queue\InteractsWithQueue;
use Laravel\Nova\Fields\ActionFields;
use App\Actions\TopupWalletAction;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Request;
use App\Models\User;

class TransferCredits extends DestructiveAction
{
    use InteractsWithQueue, Queueable;

    /**
     *
     */
    public function __construct()
    {
        $this->runCallback = function (Request $request, $model) {
            return $model instanceof User && !$model->is($request->user());
        };
    }

    /**
     * @param ActionFields $fields
     * @param Collection $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models): mixed
    {
        foreach ($models as $model) {
            if ($model instanceof User) {
                TopupWalletAction::run($model, $fields->amount);
            }
        }
    }

    /**
     * @param NovaRequest $request
     * @return array
     * @throws \Brick\Math\Exception\MathException
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function fields(NovaRequest $request): array
    {
        $min = Money::of(1000, 'PHP');
        $max = Money::of(10000000, 'PHP');
        $inc = Money::of(1000, 'PHP');
        return [
            Currency::make('Amount')
                ->required()
                ->min($min->getAmount()->toInt())->max($max->getAmount()->toInt())->step($inc->getAmount()->toInt())//TODO: put this in config
                ->default(1000)
                ->help('min of ' . $min->formatTo('en_US') . ' to max of ' . $max->formatTo('en_US') . ' in increments of ' . $inc->formatTo('en_US')),
        ];
    }
}
