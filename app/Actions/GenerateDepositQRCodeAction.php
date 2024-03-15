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
        $merchant_code = $account ? $user->merchant_code : null;
        $account = $account ?: $user->mobile;
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

        return 'data:image/png;base64,' . $data;
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException|MathException
     */
    public function handle(User $user, int $amount, string $mobile = null): string
    {
        $credits = Money::of($amount, 'PHP');

        return $this->getQRCode($user, $credits, $mobile);
    }

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'integer', 'min:50'],
            'account' => ['nullable', 'numeric', 'starts_with:0'],
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
        $user = $request->user();
        $validated = $request->validated();
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
