<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import FormField from '@/Components/FormField.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
  project: { type: Object, required: true },
  unit: { type: Object, default: null },
});

const types = ['apartment', 'villa', 'shop'];

/*
 * `status` is deliberately absent from this form. A unit's availability is derived
 * server-side from its deals and is never accepted as input — it is shown read-only.
 */
const form = useForm({
  type: props.unit?.type ?? 'apartment',
  area: props.unit?.area ?? '',
  price: props.unit?.price ?? '',
});

function submit() {
  if (props.unit) {
    form.put(`/units/${props.unit.id}`);
  } else {
    form.post(`/projects/${props.project.id}/units`);
  }
}
</script>

<template>
  <AppLayout>
    <div class="max-w-md">
      <PageHeader :title="`${unit ? 'Edit' : 'New'} Unit`" :eyebrow="project.name" />

      <div class="rounded-panel border border-line bg-surface-raised p-6">
        <div v-if="unit" class="mb-5 flex items-center gap-2">
          <span class="text-body text-ink-muted">Availability</span>
          <StatusBadge :value="unit.status" kind="availability" />
          <span class="text-support text-ink-muted">derived from this unit's deals</span>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
          <FormField id="type" label="Type" required :error="form.errors.type">
            <select id="type" v-model="form.type" class="capitalize">
              <option v-for="type in types" :key="type" :value="type" class="capitalize">{{ type }}</option>
            </select>
          </FormField>

          <FormField id="area" label="Area" required hint="m²" :error="form.errors.area">
            <input id="area" v-model="form.area" type="number" step="0.01" />
          </FormField>

          <FormField id="price" label="Price" required hint="EGP" :error="form.errors.price">
            <input id="price" v-model="form.price" type="number" step="0.01" />
          </FormField>

          <AppButton type="submit" :loading="form.processing">Save</AppButton>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
