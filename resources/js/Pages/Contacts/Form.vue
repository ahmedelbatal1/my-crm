<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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
      <h1 class="text-2xl font-bold text-slate-900 mb-6">{{ contact ? 'Edit' : 'New' }} Contact</h1>

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

          <div>
            <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
            <input
              id="phone"
              v-model="form.phone"
              type="text"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
            <p v-if="form.errors.phone" class="mt-1 text-sm text-rose-600">{{ form.errors.phone }}</p>
          </div>

          <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email (optional)</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
            <p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p>
          </div>

          <div>
            <label for="company_id" class="block text-sm font-medium text-slate-700 mb-1">Company (optional)</label>
            <select
              id="company_id"
              v-model="form.company_id"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            >
              <option :value="null">Individual buyer &mdash; no company</option>
              <option v-for="company in companies" :key="company.id" :value="company.id">
                {{ company.name }}
              </option>
            </select>
            <p v-if="form.errors.company_id" class="mt-1 text-sm text-rose-600">{{ form.errors.company_id }}</p>
          </div>

          <div v-if="salesReps.length > 0">
            <label for="user_id" class="block text-sm font-medium text-slate-700 mb-1">Owning Sales Rep</label>
            <select
              id="user_id"
              v-model="form.user_id"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            >
              <option v-for="rep in salesReps" :key="rep.id" :value="rep.id">{{ rep.name }}</option>
            </select>
            <p v-if="form.errors.user_id" class="mt-1 text-sm text-rose-600">{{ form.errors.user_id }}</p>
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
