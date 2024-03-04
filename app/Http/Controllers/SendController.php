<?php

namespace App\Http\Controllers;

use App\Actions\RequestDisbursementAction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class SendController extends Controller
{
    public function portal(Request $request)
    {
        return inertia()->render('Send/Portal');
    }

    public function disburseOne(Request $request)
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

        return back()->with([
            'data' => $response->toArray()
        ]);
    }
}
