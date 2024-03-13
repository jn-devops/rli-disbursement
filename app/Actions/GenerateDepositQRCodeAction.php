<?php

namespace App\Actions;

use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Math\Exception\NumberFormatException;
use Endroid\QrCode\{QrCode, Writer\PngWriter};
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Support\Facades\Http;
use App\Classes\Gateway;
use Brick\Money\Money;
use App\Models\User;

class GenerateDepositQRCodeAction
{
    use AsAction;

    public function __construct(protected Gateway $gateway)
    {
    }

    protected function getQRCode(User $user, Money $credits): string
    {
        $response = Http::withHeaders($this->gateway->getHeaders())->post($this->gateway->getQREndPoint(),  [
            "merchant_name" => $user->merchant_name,
            "merchant_city" => $user->merchant_city,
            "qr_type" => $credits->isZero() ? "Static" : "Dynamic",
            "qr_transaction_type" => "P2M",
            "destination_account" => $this->gateway->getDestinationAccount($user->mobile),
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
     * @throws NumberFormatException
     */
    public function handle(User $user, int $amount): string
    {
        $credits = Money::of($amount, 'PHP');

        return $this->getQRCode($user, $credits);
    }

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'integer', 'min:50']
        ];
    }

    /**
     * @param ActionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function asController(ActionRequest $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $imageBytes = $this->handle($user, $validated['amount'] ?: 0);

        return back()->with('event', [
            'name' => 'qrcode.generated',
            'data' => $imageBytes,
        ]);
    }
}
