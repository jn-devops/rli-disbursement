<?php

namespace App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use App\Transformers\StatusTransformer;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Support\Facades\Http;
use App\Models\{Reference, User};
use Spatie\Fractalistic\Fractal;
use App\Classes\Gateway;

class GetDisbursementStatusAction
{
    use AsAction;

    /**
     * @var Reference
     */
    protected Reference $reference;

    /**
     * @param Gateway $gateway
     */
    public function __construct(protected Gateway $gateway){}

    /**
     * @param User $user
     * @param string $operationId
     * @return Fractal|false
     */
    public function handle(User $user, string $operationId): Fractal|false
    {
        $response = Http::withHeaders($this->gateway->getHeaders())
            ->asJson()
            ->get($this->gateway->getStatusEndPoint($operationId));
        return $response->successful()
            ? $this->getTransformWith($response->json(), $user, $operationId)
            : false;
    }

    /**
     * @param ActionRequest $request
     * @param string $code
     * @return false|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
     */
    public function asController(ActionRequest $request, string $code): \Illuminate\Foundation\Application|\Illuminate\Http\Response|bool|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $user = $request->user();
        $this->reference = Reference::where('code', $code)->first();

        if ($fractal = $this->handle($user,  $this->reference->operation_id)) {

            return response($fractal->toArray(), 200);
        }

        return false;
    }

    /**
     * @param User $user
     * @param string $operationId
     * @return void
     */
    public function asJob(User $user, string $operationId): void
    {
        $this->handle($user, $operationId);
    }

    /**
     * @param array $json
     * @param User $user
     * @param string $operationId
     * @return Fractal
     */
    protected function getTransformWith(array $json, User $user, string $operationId): Fractal
    {
        return tap(Fractal::create()->item($json)->transformWith(new StatusTransformer($user)), function ($fractal) use ($operationId) {
            $reference = $this->reference ?? Reference::where('operation_id', $operationId)->firstOrFail();
            if ($reference->status != $fractal->toArray()) {
                $reference->status = $fractal->toArray();
                $reference->save();
            }
        });
    }
}
