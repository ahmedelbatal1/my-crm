<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import FormField from '@/Components/FormField.vue';

const props = defineProps({
  contact: { type: Object, default: null },
  companies: { type: Array, default: () => [] },
  salesReps: { type: Array, default: () => [] },
});

const form = useForm({
  name: props.contact?.name ?? '',
  phone: props.contact?.phone ?? '',
  email: props.contact?.email ?? '',
  company_id: props.contact?.company_id ?? null,
  user_id: props.contact?.user_id ?? null,
});

function submit() {
  if (props.contact) {
    form.put(`/contacts/${props.contact.id}`);
  } else {
    form.post('/contacts');
  }
}
</script>

<template>
  <AppLayout>
    <div class="max-w-md">
      <PageHeader :title="`${contact ? 'Edit' : 'New'} Contact`" />

      <div class="rounded-panel border border-line bg-surface-raised p-6">
        <form class="space-y-5" @submit.prevent="submit">
          <FormField id="name" label="Name" required :error="form.errors.name">
            <input id="name" v-model="form.name" type="text" />
          </FormField>

          <FormField id="phone" label="Phone" required :error="form.errors.phone">
            <input id="phone" v-model="form.phone" type="text" />
          </FormField>

          <FormField id="email" label="Email" :error="form.errors.email">
            <input id="email" v-model="form.email" type="email" />
          </FormField>

          <FormField id="company_id" label="Company" :error="form.errors.company_id">
            <select id="company_id" v-model="form.company_id">
              <option :value="null">Individual buyer &mdash; no company</option>
              <option v-for="company in companies" :key="company.id" :value="company.id">
                {{ company.name }}
              </option>
            </select>
          </FormField>

          <!-- Only an Admin receives salesReps, so only an Admin sees the picker. -->
          <FormField
            v-if="salesReps.length > 0"
            id="user_id"
            label="Owning sales rep"
            required
            :error="form.errors.user_id"
          >
            <select id="user_id" v-model="form.user_id">
              <option v-for="rep in salesReps" :key="rep.id" :value="rep.id">{{ rep.name }}</option>
            </select>
          </FormField>

          <AppButton type="submit" :loading="form.processing">Save</AppButton>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
