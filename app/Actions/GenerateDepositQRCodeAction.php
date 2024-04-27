<?php

namespace App\Actions;

use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Math\Exception\NumberFormatException;
use Lorisleiva\Actions\Concerns\AsAction;
use Brick\Math\Exception\MathException;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use App\Classes\Gateway;
use Brick\Money\Money;
use App\Models\User;

class GenerateDepositQRCodeAction
{
    use AsAction;

    const AMOUNT_FIELD = 'amount';
    const ACCOUNT_FIELD = 'account';

    public function __construct(protected Gateway $gateway)
    {
    }

    /**
     * @param User $user
     * @param Money $credits
     * @param string|null $account
     * @return string
     * @throws MathException
     */
    protected function getQRCode(User $user, Money $credits, string $account = null): string
    {
        logger('GenerateDepositQRCodeAction@getQRCode');
        logger('$account = ');
        logger($account);
        $merchant_code = $account ? $user->merchant_code : null;
        logger('$merchant_code = ');
        logger($merchant_code);
        $account = $account ?: $user->mobile;
        logger('$account = $account ?: $user->mobile');
        logger($account);
        $response = Http::withHeaders($this->gateway->getHeaders())->post($this->gateway->getQREndPoint(),  [
            "merchant_name" => $user->merchant_name,
            "merchant_city" => $user->merchant_city,
            "qr_type" => $credits->isZero() ? "Static" : "Dynamic",
            "qr_transaction_type" => "P2M",
            "destination_account" => $this->gateway->getDestinationAccount($account, $merchant_code),
            "resolution" => 480,
            "amount" => [
                "cur" => "PHP",
                "num" => $credits->isZero() ? '' : (string) $credits->getMinorAmount()->toInt()
            ]
        ]);
        $data = $response->json('qr_code');
        logger('data');
        logger($data);

        return 'data:image/png;base64,' . $data;
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException|MathException
     */
    public function handle(User $user, int $amount = null, string $account = null): string
    {
        $credits = Money::of($amount ?: 0, 'PHP');

        return $this->getQRCode($user, $credits, $account);
    }

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            self::AMOUNT_FIELD => ['nullable', 'integer', 'min:50'],
            self::ACCOUNT_FIELD => ['nullable', 'numeric', 'starts_with:0', 'max_digits:11'],
        ];
    }

    /**
     * @param ActionRequest $request
     * @return RedirectResponse
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException|MathException
     */
    public function asController(ActionRequest $request): RedirectResponse
    {
        logger('GenerateDepositQRCodeAction@asController');
        $user = $request->user();
        logger('$user = ');
        logger($user);
        $validated = $request->validated();
        logger('validated =');
        logger($validated);
        logger('$validated[amount]');
        logger($validated['amount']);
        logger('Arr::get($validated, account)');
        logger(Arr::get($validated, 'account'));
        $imageBytes = $this->handle($user, $validated['amount'] ?: 0, Arr::get($validated, 'account'));

        return back()->with('event', [
            'name' => 'qrcode.generated',
            'data' => $imageBytes,
        ]);
    }

    /**
     * @param RedirectResponse $response
     * @param ActionRequest $request
     * @return string
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws MathException
     */
    public function jsonResponse(RedirectResponse $response, ActionRequest $request): string
    {
        $user = $request->user();
        $validated = $request->validated();

        return $this->handle($user, $validated['amount'] ?: 0);
    }
}
