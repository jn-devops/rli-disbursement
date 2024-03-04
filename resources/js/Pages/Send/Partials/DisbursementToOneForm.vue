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

defineProps({
    agent: Object,
});

const form = useForm({
    reference: null,
    bank: null,
    account_number: null,
    via: null,
    amount: null,
});

const send = () => {
    form.post(route('disburse-one'), {
        errorBag: 'disburse-one',
        preserveScroll: true,
        onSuccess: () => form.reset()
    });
};

let PHPeso = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'PHP',
});

const amountAdded = ref(0);
const bankSentTo = ref(null);

watch (
    () => usePage().props.flash.event,
    (event) => {
        switch (event?.name) {
            case 'amount.disbursed':
                console.log(event?.data);
                amountAdded.value = event?.data.amount;
                bankSentTo.value =  event?.data.bank_name;
                break;
        }
    },
    { immediate: true }
);
</script>

<template>
    <FormSection @submitted="send">
        <template #title>
            Send To One
        </template>
        <template #description>
            Send to a bank account. Your balance is {{ PHPeso.format(agent.balanceFloat) }}.
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="amount" value="Amount" />
                <TextInput
                    id="amount"
                    v-model="form.amount"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                />
                <InputError :message="form.errors.amount" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="bank" value="Bank" />
                <TextInput
                    id="bank"
                    v-model="form.bank"
                    type="text"
                    class="mt-1 block w-full"
                    required
                />
                <InputError :message="form.errors.bank" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="account_number" value="Account Number" />
                <TextInput
                    id="account_number"
                    v-model="form.account_number"
                    type="text"
                    class="mt-1 block w-full"
                    required
                />
                <InputError :message="form.errors.account_number" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="via" value="Via" />
                <TextInput
                    id="via"
                    v-model="form.via"
                    type="text"
                    class="mt-1 block w-full"
                    required
                />
                <InputError :message="form.errors.via" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="reference" value="Reference" />
                <TextInput
                    id="reference"
                    v-model="form.reference"
                    type="text"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.reference" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                {{  PHPeso.format(amountAdded) }} sent to {{ bankSentTo }}.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Send
            </PrimaryButton>
        </template>
    </FormSection>
</template>

<style scoped>

</style>
