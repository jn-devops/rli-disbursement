<?php

namespace App\Actions;

use Brick\Math\Exception\{MathException, NumberFormatException, RoundingNecessaryException};
use App\Data\{AmountData, DestinationAccountData, GatewayResponseData, RecipientData};
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Brick\Money\Exception\UnknownCurrencyException;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Support\Facades\Http;
use App\Classes\{Address, Gateway};
use Illuminate\Validation\Rule;
use App\Models\{Product, User};
use Bavix\Wallet\Objects\Cart;
use Illuminate\Support\Arr;
use Brick\Money\Money;

/**
 * Class RequestDisbursementAction
 *
 * @method   mixed    execute()
 */
class RequestDisbursementAction
{
    use AsAction;

    protected string $currency = 'PHP';

    public function __construct(protected Gateway $gateway)
    {
    }

    /**
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws ExceptionInterface
     * @throws MathException
     */
    protected function disburse(User $user, array $validated): GatewayResponseData|bool
    {
        //TODO: transform payload array to data
        $payload = [
            "reference_id" => Arr::get($validated, 'reference'),
            "settlement_rail" => Arr::get($validated, 'via'),
            "amount" => $amount = $this->getAmountArray($validated),
            "source_account_number" => config('disbursement.source.account_number'),
            "sender" => config('disbursement.source.sender'),
            "destination_account" => $this->getDestinationAccount($validated),
            "recipient" => $this->getRecipient($validated)
        ];
        logger($payload);
        $response = Http::withHeaders($this->gateway->getHeaders())
            ->asJson()
            ->post(
                $this->gateway->getEndPoint(), $payload
            );

        $credits = Money::of(Arr::get($validated, 'amount'), 'PHP');
        $meta = [
            'operationId' => $response->json('transaction_id'),
            'details' => $payload
        ];
        $transaction = $user->withdraw($credits->getMinorAmount()->toInt(), $meta, false);
        /*************************** start of service fee ****************************/
        $product_qty_list = [
            'transaction_fee' => 1, //qty per transaction
            'merchant_discount_rate' => $credits->getAmount()->toInt(), //qty per peso
        ];

        $cart = with(app(Cart::class), function ($cart) use ($user, $product_qty_list, &$sf) {
            $collection = tap(Product::query()->whereIn('code', array_keys($product_qty_list))->get(), function ($products) use (&$cart, $user, $product_qty_list, &$service_fees, &$sf) {
                foreach ($products as $product) {
                    $qty = $product_qty_list[$product->code];
                    $cart = $cart->withItem($product->setQty($qty), quantity: 1);
                }
            });

            return $cart;
        });
        $user->payCart($cart);
        /*************************** end of service fee ****************************/

        $responseData = array_merge(['uuid' => $transaction->uuid], $response->json());

        return $response->successful() ? GatewayResponseData::from($responseData) : false;
    }

    /**
     * @param User $user
     * @param array $validated
     * @return GatewayResponseData|bool
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws ExceptionInterface
     * @throws MathException
     */
    public function handle(User $user, array $validated): GatewayResponseData|bool
    {
        return $this->disburse($user, $validated);
    }

    /**
     * @return array[]
     */
    public function rules(): array
    {
        $min = config('disbursement.min');
        $min_rule = 'min:' . $min;
        $max = config('disbursement.max');
        $max_rule = 'max:' . $max;
        $settlement_rails = config('disbursement.settlement_rails');

        return [
            'reference' => ['required', 'string', 'min:2'],
            'bank' => ['required', 'string'],
            'account_number' => ['required', 'string'],
            'via' => ['required', 'string', Rule::in($settlement_rails)],
            'amount' => ['required', 'integer', $min_rule, $max_rule],
        ];
    }

    /**
     * @param ActionRequest $request
     * @return \Illuminate\Http\Response
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws ExceptionInterface
     * @throws MathException
     */
    public function asController(ActionRequest $request): \Illuminate\Http\Response
    {
        $user = $request->user();
        logger('RequestDisbursementAction@asController');
        logger('$user->toArray()');
        logger($user->toArray());
        logger('$request->all()');
        logger($request->all());
        logger('$request->validated()');
        logger($request->validated());

        $response = $this->disburse($user, $request->validated());
        logger('response->toJson()');
        logger($response->toJson());

        return response($response->toJson(), 200);
    }

    /**
     * @param array $validated
     * @return array
     */
    protected function getDestinationAccount(array $validated): array
    {
        return DestinationAccountData::from([
            'bank_code' => Arr::get($validated, 'bank'),
            'account_number' => Arr::get($validated, 'account_number')
        ])->toArray();
    }

    /**
     * @param array $validated
     * @return array
     */
    protected function getRecipient(array $validated): array
    {
        return RecipientData::from(
            [
                "name" => Arr::get($validated, 'account_number'),
                'address' => Address::generate()
            ]
        )->toArray();
    }

    /**
     * @param array $validated
     * @return array
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws MathException
     */
    protected function getAmountArray(array $validated): array
    {
        $minor_amount = Money::of(Arr::get($validated, 'amount'), $this->currency)->getMinorAmount()->toInt();

        return AmountData::from([
            'cur' => $this->currency,
            'num' => (string) $minor_amount
        ])->toArray();
    }
}
