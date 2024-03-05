<script setup>
import DisbursementToOneForm from "@/Pages/Outgoing/Partials/DisbursementCredits.vue";
import TransferCredits from "@/Pages/Outgoing/Partials/TransferCredits.vue";
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
    <AppLayout title="Send">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Outgoing
            </h2>
            <h3>
                <span>Wallet Balance ({{ $page.props.agent.mobile }})</span>:  <span></span><span>{{ PHPeso.format($page.props.agent.balanceFloat) }}</span>
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
            </div>
        </div>
    </AppLayout>
</template>
