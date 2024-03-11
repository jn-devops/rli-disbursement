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
import Dropdown from "@/Components/Dropdown.vue";
import { ref, watch } from 'vue';

const props = defineProps({
    agent: Object,
    rails: {
        type: Array,
        default: [{id: 1, name: 'INSTAPAY'}, {id: 2, name: 'PESONET'}]
    }
});

const form = useForm({
    reference: null,
    bank: 'GXCHPHM2XXX',
    account_number: null,
    via: props.rails[0].name,
    amount: '50',
});

const send = () => {
    form.post(route('disburse'), {
        errorBag: 'disburse',
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
            Disburse Credits To Account
        </template>
        <template #description>
            <div>Wallet to Bank Account Transfer</div>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="amount" value="Amount" />
                <TextInput
                    id="amount"
                    v-model="form.amount"
                    type="number"
                    class="mt-1 block w-full"
                    min="50"
                    placeholder="minimum 50 credits"
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
                    placeholder="input bank code here - HTTP GET /api/banks for list"
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
                    placeholder="e.g., 09171234567"
                    required
                />
                <InputError :message="form.errors.account_number" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="via2" value="Via" />
                <Dropdown class="mt-1 block w-full">
                    <template #trigger>
                        <div class="relative">
                            <button type="button"
                                    class="inline-flex justify-between w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150"
                                    id="options-menu" aria-haspopup="true" aria-expanded="true">
                                <span v-if="form.via === ''">Select Rails</span>
                                <span v-else>{{ form.via }}</span>
                                <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                     fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd"
                                          d="M5 8a1 1 0 011.707 0L10 11.293l3.293-3.294a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4A1 1 0 015 8z"
                                          clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </template>
                    <template #content>
                        <div>
                            <div class="block px-4 py-2 text-xs text-gray-400">
                                Settlement Rail
                            </div>
                            <button v-for="rail in props.rails" :key="rail.id" type="button"
                                    class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
                                    role="menuitem" @click="form.via = rail.name">
                                {{ rail.name }}
                            </button>
                        </div>
                    </template>
                </Dropdown>
                <InputError :message="form.errors.via" class="mt-2" />
            </div>
<!--            <div class="col-span-6 sm:col-span-4">-->
<!--                <InputLabel for="via" value="Via" />-->
<!--                <TextInput-->
<!--                    id="via"-->
<!--                    v-model="form.via"-->
<!--                    type="text"-->
<!--                    class="mt-1 block w-full"-->
<!--                    required-->
<!--                />-->
<!--                <InputError :message="form.errors.via" class="mt-2" />-->
<!--            </div>-->
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
