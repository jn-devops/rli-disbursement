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
    user: Object
})

let PHPeso = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'PHP',
});

let Percent = new Intl.NumberFormat('en-US', {
    style: 'percent',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
});

const form = useForm({
    code: null,
});

const confirmingTopup = ref(false);
const codeInput = ref(false);

const showModal = () => {
    confirmingTopup.value = true;
    setTimeout(() => codeInput.value.focus(), 250);
}

const closeModal = () => {
    confirmingTopup.value = false;
}

const update = () => {
    form.post(route('update-fees'), {
        errorBag: 'update-fees',
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        }
    });
};
</script>

<template>
    <FormSection>
        <template #title>
            Service Fees
        </template>
        <template #description>
            Update your fee structure. {{ form.code }}
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="transaction_fee" value="Transaction Fee" />
                <TextInput
                    id="transaction_fee"
                    :value="PHPeso.format(user.tf/100)"
                    type="text"
                    class="mt-1 block w-full"
                    readonly
                />
            </div>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="merchant_discount_rate" value="Merchant Discount Rate" />
                <TextInput
                    id="merchant_discount_rate"
                    :value="Percent.format(user.mdr/100)"
                    type="text"
                    class="mt-1 block w-full"
                    readonly
                />
            </div>
        </template>
        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Updated
            </ActionMessage>

            <SecondaryButton @click.prevent="showModal">
                Update
            </SecondaryButton>

            <DialogModal :show="confirmingTopup" @close="closeModal">
                <template #title>
                    Update Service Fees
                </template>
                <template #content>
                    <InputLabel for="voucher-code" value="Enter Code" />
                    <TextInput
                        id="voucher-code"
                        ref="codeInput"
                        v-model="form.code"
                        type="text"
                        class="mt-1 block w-full"
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.code" />
                </template>
                <template #footer>
                    <div class="flex items-center space-x-4 px-4 py-3">
                        <SecondaryButton @click="closeModal()">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton @click.prevent="update();">
                            Submit
                        </PrimaryButton>
                    </div>
                </template>
            </DialogModal>
        </template>
    </FormSection>
</template>

<style scoped>

</style>
