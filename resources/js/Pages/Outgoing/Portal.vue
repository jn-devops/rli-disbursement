<script setup>
import DisbursementToOneForm from "@/Pages/Outgoing/Partials/DisbursementCredits.vue";
import TransferCredits from "@/Pages/Outgoing/Partials/TransferCredits.vue";
import DepositAmount from "@/Pages/Outgoing/Partials/DepositAmount.vue";
import PayAmount from "@/Pages/Outgoing/Partials/PayAmount.vue";
import SectionBorder from '@/Components/SectionBorder.vue';
import { router, usePage } from "@inertiajs/vue3";
import AppLayout from '@/Layouts/AppLayout.vue';

Echo.private(`App.Models.User.${ usePage().props.agent.id }`)
    .listen('.disbursement.confirmed', (e) => {
        router.reload();
        console.log(e);
    })

let PHPeso = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'PHP',
});
</script>

<template>
    <AppLayout title="Outgoing">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Outgoing
            </h2>
            <h3 class="dark:text-white white:text-black">
                <span>Wallet Balance ({{ $page.props.agent.mobile }})</span>:  &nbsp;&nbsp;<span></span><span>{{ PHPeso.format($page.props.agent.balanceFloat) }}</span>
            </h3>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <div>
                    <TransferCredits :agent="$page.props.agent"/>
                    <SectionBorder />
                </div>
                <div>
                    <DisbursementToOneForm :agent="$page.props.agent"/>
                    <SectionBorder />
                </div>
                <div>
                    <DepositAmount :agent="$page.props.agent"/>
                    <SectionBorder />
                </div>
                <div>
                    <PayAmount :agent="$page.props.agent"/>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
