<script setup>
import Welcome from '@/Pages/Disbursement/Welcome.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import {router, usePage} from "@inertiajs/vue3";

Echo.channel(`App.Models.User.${ usePage().props.agent.id }`)
    .listen('.disbursement.confirmed', (e) => {
        router.reload();
        console.log(e);
    })
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <Welcome />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
