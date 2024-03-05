<?php

namespace App\Http\Controllers;

use App\Actions\RequestDisbursementAction;
use Illuminate\Support\Facades\Validator;
use App\Actions\TopupWalletAction;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\User;

class SendController extends Controller
{
    public function outgoing(Request $request)
    {
        return inertia()->render('Outgoing/Portal');
    }

    public function disburse(Request $request)
    {
        $validated = Validator::validate($request->all(), [
            'reference' => ['nullable', 'string'],
            'bank' => ['required', 'string'],
            'account_number' => ['required', 'string'],
            'via' => ['required', 'string'],
            'amount' => ['required', 'int', 'min:1'],
        ]);
        $user = $request->user();
        $response = RequestDisbursementAction::run($user, $validated);

        $bank_name = $validated['bank'];
        $via = $validated['via'];
        $amount = $validated['amount'];

        return back()->with('event', [
            'name' => 'amount.disbursed',
            'data' => compact('bank_name', 'via', 'amount'),
        ]);
    }

    public function transfer(Request $request)
    {
        $validated = Validator::validate($request->all(), [
            'reference' => ['nullable', 'string'],
            'account_number' => ['required', 'string', 'exists:users,mobile'],
            'amount' => ['required', 'int', 'min:1'],
        ]);

        $source = $request->user();
        $mobile = Arr::get($validated, 'account_number');
        $destination = User::where('mobile', $mobile)->first();
        $amountFloat = Arr::get($validated, 'amount');
        $transfer = app(TopupWalletAction::class)
            ->setSource($source)
            ->handle($destination, $amountFloat);//handle is used instead of run

        return back()->with('event', [
            'name' => 'amount.credited',
            'data' => [
                'amountAdded' => $amountFloat,
                'accountNumberSentTo' => $mobile
            ],
        ]);
    }
}
