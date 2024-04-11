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

    public function __construct(protected Gateway $gateway){}

    public function handle(User $user, string $operationId): Fractal|false
    {
        $response = Http::withHeaders($this->gateway->getHeaders())
            ->asJson()
            ->get($this->gateway->getStatusEndPoint($operationId));

        return $response->successful()
            ? Fractal::create()->item($response->json())->transformWith(new StatusTransformer($user))
            : false;
    }

    public function asController(ActionRequest $request, string $code)
    {
        $user = $request->user();
        $reference = Reference::where('code', $code)->first();

        if ($fractal = $this->handle($user,  $reference->operation_id)) {
            return response($fractal->toArray(), 200);
        }

        return false;
    }
}
