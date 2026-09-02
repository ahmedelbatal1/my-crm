<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import FormField from '@/Components/FormField.vue';

const props = defineProps({
  project: { type: Object, default: null },
});

const form = useForm({
  name: props.project?.name ?? '',
  location: props.project?.location ?? '',
  description: props.project?.description ?? '',
});

function submit() {
  if (props.project) {
    form.put(`/projects/${props.project.id}`);
  } else {
    form.post('/projects');
  }
}
</script>

<template>
  <AppLayout>
    <div class="max-w-md">
      <PageHeader :title="`${project ? 'Edit' : 'New'} Project`" />

      <div class="rounded-panel border border-line bg-surface-raised p-6">
        <form class="space-y-5" @submit.prevent="submit">
          <FormField id="name" label="Name" required :error="form.errors.name">
            <input id="name" v-model="form.name" type="text" />
          </FormField>

          <FormField id="location" label="Location" :error="form.errors.location">
            <input id="location" v-model="form.location" type="text" />
          </FormField>

          <FormField id="description" label="Description" :error="form.errors.description">
            <textarea id="description" v-model="form.description" rows="3" />
          </FormField>

          <AppButton type="submit" :loading="form.processing">Save</AppButton>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
