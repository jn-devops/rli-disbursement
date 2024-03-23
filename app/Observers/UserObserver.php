<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        if (null == $user->merchant_name) {
            $user->merchant_name = $user->name;
        }
        if (null == $user->merchant_city) {
            $user->merchant_city = config('disbursement.merchant.default.city');
        }
//        if (null == $user->tf) {
//            $user->tf = config('disbursement.user.tf');
//        }
//        if (null == $user->mdr) {
//            $user->mdr = config('disbursement.user.mdr');
//        }
        if (null == $user->transaction_fee) {
            $user->transaction_fee = config('disbursement.user.transaction_fee');
        }
        if (null == $user->merchant_discount_rate) {
            $user->merchant_discount_rate = config('disbursement.user.merchant_discount_rate');
        }

        if (null == $user->merchant_code) {
            $user->merchant_code = (string) $user->id;
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
