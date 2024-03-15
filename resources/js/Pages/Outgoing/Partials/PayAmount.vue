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
const imageBytes = ref(null);

const form = useForm({
    account: null,
    amount: null,
});

const generate = () => {
    form.post(route('generate-qr'), {
        errorBag: 'generate',
        preserveScroll: true,
        onSuccess: showModal
    });
};

const share = () => {
    confirmingTopup.value = false;
}
const showModal = () => {
    confirmingTopup.value = true;
    // setTimeout(() => codeInput.value.focus(), 250);
}

const closeModal = () => {
    confirmingTopup.value = false;
}

watch (
    () => usePage().props.flash.event,
    (event) => {
        switch (event?.name) {
            case 'qrcode.generated':
                console.log(event?.data);
                imageBytes.value = event?.data;
                break;
        }
    },
    { immediate: true }
);
</script>

<template>
    <FormSection @submitted="generate">
        <template #title>
            Request for Payment
        </template>
        <template #description>
            <div>Optionally embed an amount in a QR Code for Payment</div>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="mobile" value="Account # (mobile)" />
                <TextInput
                    id="mobile"
                    v-model="form.account"
                    type="Text"
                    class="mt-1 block w-full"
                    placeholder="09171234567"
                    autofocus
                />
                <InputError :message="form.errors.account" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="amount" value="Amount" />
                <TextInput
                    id="amount"
                    v-model="form.amount"
                    type="Text"
                    class="mt-1 block w-full"
                    placeholder="minimum 50 credits"
                />
                <InputError :message="form.errors.amount" class="mt-2" />
            </div>

        </template>
        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Generated
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Generate
            </PrimaryButton>

            <DialogModal :show="confirmingTopup" @close="closeModal">
                <template #title>
                    Pay QR Code
                </template>
                <template #content>
                    <div class="col-span-6 sm:col-span-4">
                        <img :src="imageBytes" alt="qr-code"/>
                    </div>
                </template>
                <template #footer>
                    <div class="flex items-center space-x-4 px-4 py-3">
                        <SecondaryButton @click="closeModal()">
                            Close
                        </SecondaryButton>
                        <PrimaryButton @click.prevent="share">
                            Share
                        </PrimaryButton>
                    </div>
                </template>
            </DialogModal>
        </template>
    </FormSection>
</template>

<style scoped>

</style>
