<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  companies: { type: Array, required: true },
});
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-900">Companies</h1>
      <Link
        href="/companies/create"
        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition"
      >
        New Company
      </Link>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl divide-y divide-slate-100 overflow-hidden">
      <Link
        v-for="company in companies"
        :key="company.id"
        :href="`/companies/${company.id}/edit`"
        class="block px-5 py-4 hover:bg-slate-50 transition text-sm font-medium text-slate-800"
      >
        {{ company.name }}
      </Link>
      <p v-if="companies.length === 0" class="px-5 py-8 text-center text-sm text-slate-400">
        No companies yet.
      </p>
    </div>
  </AppLayout>
</template>
