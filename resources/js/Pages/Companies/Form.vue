<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import FormField from '@/Components/FormField.vue';

const props = defineProps({
  company: { type: Object, default: null },
});

const form = useForm({
  name: props.company?.name ?? '',
});

function submit() {
  if (props.company) {
    form.put(`/companies/${props.company.id}`);
  } else {
    form.post('/companies');
  }
}
</script>

<template>
  <AppLayout>
    <div class="max-w-md">
      <PageHeader :title="`${company ? 'Edit' : 'New'} Company`" />

      <div class="rounded-panel border border-line bg-surface-raised p-6">
        <form class="space-y-5" @submit.prevent="submit">
          <FormField id="name" label="Name" required :error="form.errors.name">
            <input id="name" v-model="form.name" type="text" />
          </FormField>

          <AppButton type="submit" :loading="form.processing">Save</AppButton>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
