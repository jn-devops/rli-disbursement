<?php

namespace App\Actions;

use App\Data\{AmountData, DestinationAccountData, GatewayResponseData,  RecipientData};
use App\Models\Product;
use Bavix\Wallet\Objects\Cart;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Support\Facades\Http;
use App\Classes\{Address, Gateway, ServiceFee};
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;
use Brick\Money\Money;
use ReflectionMethod;
use App\Models\User;

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

//    public static function execute(...$arguments): mixed
//    {
//        $action = static::make();
//        $argName = $action->validated ?? 'validated';
//        foreach ((new ReflectionMethod(static::class, 'handle'))->getParameters() as $parameter) {
//            if (($parameter->name == $argName) and ($parameter->getType() == 'array')) {
//                $attribs = func_get_args()[$parameter->getPosition()];
//
//                return $action->handle(Validator::validate($attribs, $action->rules()));
//            }
//        }
//
//        return $action->handle(...$arguments);
//    }

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
        $serviceFee = (new ServiceFee($user))->compute($credits);
        $minor_amount = $serviceFee->inclusive()->getMinorAmount()->toInt();
        $meta = [
            'operationId' => $response->json('transaction_id'),
            'details' => $payload
        ];
//        $transaction = $user->withdraw($minor_amount, $meta, false);
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

    public function asController(ActionRequest $request): \Illuminate\Http\Response
    {
        $user = $request->user();
        $response = $this->disburse($user, $request->validated());

        return response($response->toJson(), 200);
    }

    protected function getDestinationAccount(array $validated): array
    {
        return DestinationAccountData::from([
            'bank_code' => Arr::get($validated, 'bank'),
            'account_number' => Arr::get($validated, 'account_number')
        ])->toArray();
    }

    protected function getRecipient(array $validated): array
    {
        return RecipientData::from(
            [
                "name" => Arr::get($validated, 'account_number'),
                'address' => Address::generate()
            ]
        )->toArray();
    }

    protected function getAmountArray(array $validated): array
    {
        $minor_amount = Money::of(Arr::get($validated, 'amount'), $this->currency)->getMinorAmount()->toInt();

        return AmountData::from([
            'cur' => $this->currency,
            'num' => (string) $minor_amount
        ])->toArray();
    }
}
