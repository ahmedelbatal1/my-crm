<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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
      <h1 class="text-2xl font-bold text-slate-900 mb-6">{{ company ? 'Edit' : 'New' }} Company</h1>

      <div class="bg-white border border-slate-200 rounded-xl p-6">
        <form class="space-y-5" @submit.prevent="submit">
          <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Name</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p>
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
