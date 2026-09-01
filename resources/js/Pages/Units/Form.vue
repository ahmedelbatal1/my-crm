<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  project: { type: Object, required: true },
  unit: { type: Object, default: null },
});

const types = ['apartment', 'villa', 'shop'];

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
      <p class="text-sm text-slate-500 mb-1">{{ project.name }}</p>
      <h1 class="text-2xl font-bold text-slate-900 mb-6">{{ unit ? 'Edit' : 'New' }} Unit</h1>

      <div class="bg-white border border-slate-200 rounded-xl p-6">
        <form class="space-y-5" @submit.prevent="submit">
          <div>
            <label for="type" class="block text-sm font-medium text-slate-700 mb-1">Type</label>
            <select
              id="type"
              v-model="form.type"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm capitalize focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            >
              <option v-for="type in types" :key="type" :value="type" class="capitalize">{{ type }}</option>
            </select>
            <p v-if="form.errors.type" class="mt-1 text-sm text-rose-600">{{ form.errors.type }}</p>
          </div>

          <div>
            <label for="area" class="block text-sm font-medium text-slate-700 mb-1">Area (m&sup2;)</label>
            <input
              id="area"
              v-model="form.area"
              type="number"
              step="0.01"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
            <p v-if="form.errors.area" class="mt-1 text-sm text-rose-600">{{ form.errors.area }}</p>
          </div>

          <div>
            <label for="price" class="block text-sm font-medium text-slate-700 mb-1">Price</label>
            <input
              id="price"
              v-model="form.price"
              type="number"
              step="0.01"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
            <p v-if="form.errors.price" class="mt-1 text-sm text-rose-600">{{ form.errors.price }}</p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition disabled:opacity-50"
          >
            Save
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
