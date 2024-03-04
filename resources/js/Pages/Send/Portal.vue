<script setup>
import DisbursementToOneForm from "@/Pages/Send/Partials/DisbursementToOneForm.vue";
import SectionBorder from '@/Components/SectionBorder.vue';
import { router, usePage } from "@inertiajs/vue3";
import AppLayout from '@/Layouts/AppLayout.vue';

Echo.private(`App.Models.User.${ usePage().props.agent.id }`)
    .listen('.disbursement.confirmed', (e) => {
        router.reload();
        console.log(e);
    })
</script>

<template>
    <AppLayout title="Send">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Send
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <div>
                    <DisbursementToOneForm :agent="$page.props.agent"/>
                    <SectionBorder />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
