<?php

namespace App\Actions;

use Brick\Math\Exception\{MathException, NumberFormatException, RoundingNecessaryException};
use App\Data\{AmountData, DestinationAccountData, GatewayResponseData, RecipientData};
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Brick\Money\Exception\UnknownCurrencyException;
use App\Models\{Product, Reference, User};
use Lorisleiva\Actions\Concerns\AsAction;
use App\Events\DisbursementRequested;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Http;
use App\Classes\{Address, Gateway};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Bavix\Wallet\Objects\Cart;
use Illuminate\Support\Arr;
use Brick\Money\Money;
use Closure;

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
        $credits = Money::of(Arr::get($validated, 'amount'), 'PHP');

        DB::beginTransaction();
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

        /*************************** start of withdrawal ****************************/
        $transaction = $user->withdraw($credits->getMinorAmount()->toInt(), [], false);
        /*************************** end of disbursement ****************************/

        /********************* start of disbursement request ************************/
        $payload = [
            "reference_id" => $reference =  Arr::get($validated, 'reference'),
            "settlement_rail" => Arr::get($validated, 'via'),
            "amount" => $amount = $this->getAmountArray($validated),
            "source_account_number" => config('disbursement.source.account_number'),
            "sender" => config('disbursement.source.sender'),
            "destination_account" => $this->getDestinationAccount($validated),
            "recipient" => $this->getRecipient($validated)
        ];
        logger('RequestDisbursementAction@disburse');
        logger('$payload = ');
        logger($payload);

        $response = Http::withHeaders($this->gateway->getHeaders())
            ->asJson()
            ->post(
                $this->gateway->getDisbursementEndPoint(), $payload
            );

        /********************* end of disbursement request ************************/
        if ($response->successful()) {
            //TODO: deprecate $meta
            $meta = [
                'operationId' => $operationId = $response->json('transaction_id'),
                'details' => $payload,//TODO: deprecate
                'request' => [
                    'user_id' => $user->id,
                    'payload' => $payload
                ]
            ];
            $transaction->meta = $meta;
            $transaction->save();
            DB::commit();
            DisbursementRequested::dispatch($transaction, $validated, $payload, $response->json());
            $responseData = array_merge(['uuid' => $transaction->uuid], $response->json());

            return GatewayResponseData::from($responseData);
        }
        else {
            DB::rollBack();

            return false;
        }
    }

    /**
     * @param User $user
     * @param array $attribs
     * @return GatewayResponseData|bool
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws ExceptionInterface
     * @throws MathException
     */
    public function handle(User $user, array $attribs): GatewayResponseData|bool
    {
        $validated = \Illuminate\Support\Facades\Validator::validate($attribs, $this->rules());

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

    public function afterValidator(Validator $validator, ActionRequest $request): void
    {
        $user = $request->user();
        $minor_amount = Money::of(Arr::get($request->all(), 'amount'), $this->currency)->getMinorAmount()->toInt();
//        $minor_threshold = Money::of(config('disbursement.threshold_balance'), $this->currency)->getMinorAmount()->toInt();
        $minor_threshold = 0;
        if (($user->balance - $minor_threshold) <= $minor_amount) {
        if (true) {
                $validator->errors()->add('amount', 'Insufficient Funds');
            }
        }
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
