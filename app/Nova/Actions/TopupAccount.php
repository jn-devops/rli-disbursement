<?php

namespace App\Nova\Actions;

use Laravel\Nova\Actions\{Action, ActionResponse};
use App\Actions\GenerateDepositQRCodeAction;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\ActionFields;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Currency;
use Illuminate\Http\Request;
use Brick\Money\Money;
use App\Models\User;

class TopupAccount extends Action
{
    /**
     * @var string
     */
    protected string $title = 'Topup Account';

    /**
     * @var GenerateDepositQRCodeAction|(GenerateDepositQRCodeAction&\Illuminate\Contracts\Foundation\Application)|\Illuminate\Contracts\Foundation\Application|\Illuminate\Foundation\Application|mixed
     */
    protected GenerateDepositQRCodeAction $action;

    /**
     *
     */
    public function __construct()
    {
        $this->action = app(GenerateDepositQRCodeAction::class);
        $this->runCallback = function (Request $request, $model) {
            return $model instanceof User && $model->is($request->user());
        };
    }

    /**
     * @param string $html
     * @return Action|ActionResponse
     */
    protected function showQRCode(string $html): ActionResponse|Action
    {
        return Action::modal('modal-response', [
            'title' => $this->title,
            'html' => $html
        ]);
    }

    /**
     * $arg1 amount
     * $arg2 account if present i.e., mobile number
     *
     * @param User $user
     * @param ...$args
     * @return string
     */
    protected function generateQRCode(User $user, ...$args): string
    {
        $imageBytes = $this->action->run($user, ...$args);

        return __('<div class="col-span-6 sm:col-span-4 mx-auto"><img src=":src" alt="qr-code" class="mx-auto"/></div>', ['src' => $imageBytes]);
    }

    /**
     * @param ActionFields $fields
     * @param Collection $models
     * @return Action|ActionResponse
     */
    public function handle(ActionFields $fields, Collection $models): ActionResponse|Action
    {
        $user = $models->first();

        return $this->showQRCode($this->generateQRCode($user, ...$fields->toArray()));
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
        $min = Money::of(config('disbursement.min'), 'PHP');
        $max = Money::of(config('disbursement.max'), 'PHP');
        $inc = Money::of(10, 'PHP');
        $hlp = 'blank means user will input; min of ' . $min->formatTo('en_US') . ' to max of ' . $max->formatTo('en_US') . ' in increments of ' . $inc->formatTo('en_US');

        return [
            Currency::make('Amount')
                ->rules($this->action->rules()[$this->action::AMOUNT_FIELD])
                ->min($min->getAmount()->toInt())
                ->max($max->getAmount()->toInt())
                ->step($inc->getAmount()->toInt())
                ->help($hlp),
        ];
    }
}
