<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ActionMessage from "@/Components/ActionMessage.vue";
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FormSection from '@/Components/FormSection.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from "@inertiajs/vue3";
import { ref, watch } from 'vue';

const confirmingTopup = ref(false);
const amountAdded = ref(0);

const showModal = () => {
    confirmingTopup.value = true;
    setTimeout(() => amountAdded.value.focus(), 250);
}

const closeModal = () => {
    confirmingTopup.value = false;
}

const topup = () => {
    form.post(route('topup-wallet'), {
        errorBag: 'topup-wallet',
        preserveScroll: true,
        onSuccess: () => form.reset()
    });
};

const form = useForm({
    amount: null,
});

watch (
    () => usePage().props.flash.event,
    (event) => {
        switch (event?.name) {
            case 'amount.deposited':
                console.log(event?.data);
                amountAdded.value = event?.data.deposit.amount;
                break;
        }
    },
    { immediate: true }
);

let PHPeso = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'PHP',
});

</script>

<template>
    <FormSection @submitted="topup">
        <template #title>
            Wallet Information
        </template>
        <template #description>
            Update your wallet balance.
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="balance" value="Balance" />
                <TextInput
                    id="balance"
                    :value="PHPeso.format(usePage().props.agent.balanceFloat)"
                    type="string"
                    class="mt-1 block w-full"
                    readonly
                />
                <InputError :message="form.errors.balance" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                {{  PHPeso.format(amountAdded/100) }} added to your wallet.
            </ActionMessage>

            <SecondaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="showModal()">
                Topup
            </SecondaryButton>
            <DialogModal :show="confirmingTopup" @close="closeModal">
                <template #title>
                    Topup Wallet
                </template>
                <template #content>
                    <InputLabel for="amount" value="Amount" />
                    <TextInput
                        id="amount"
                        ref="amountAdded"
                        v-model="form.amount"
                        type="number"
                        class="mt-1 block w-full"
                        autofocus
                    />
                </template>
                <template #footer>
                    <div class="flex items-center space-x-4 px-4 py-3">
                        <SecondaryButton @click="closeModal()">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton @click="closeModal(); topup();">
                            Go
                        </PrimaryButton>
                    </div>
                </template>
            </DialogModal>
        </template>
    </FormSection>
</template>

<style scoped>

</style>
