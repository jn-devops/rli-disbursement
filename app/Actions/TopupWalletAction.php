<?php

namespace App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Support\Arr;
use App\Models\User;

class TopupWalletAction
{
    use AsAction;

    protected User $source;

    public function __construct()
    {
        $this->setSource(User::getSystem());
//        $this->source = User::getSystem();
    }

    /**
     * @param User $user
     * @param float $amount
     * @return \Bavix\Wallet\Models\Transfer
     * @throws \Bavix\Wallet\Internal\Exceptions\ExceptionInterface
     */
    public function handle(User $user, float $amount): \Bavix\Wallet\Models\Transfer
    {
        return $this->source->transferFloat($user, $amount);
    }

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'int', 'min:1'],
        ];
    }

    /**
     * @param ActionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Bavix\Wallet\Internal\Exceptions\ExceptionInterface
     */
    public function asController(ActionRequest $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $amount = Arr::get($request->validated(), 'amount');
        $transfer = $this->handle($user, $amount);

        return back()->with('event', [
            'name' => 'amount.deposited',//TODO: change to amount.credited
            'data' => $transfer->toArray(),
        ]);
    }

    public function setSource(User $source): static
    {
        $this->source = $source;

        return $this;
    }
}
