<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  dealsByStage: { type: Object, required: true },
});

const columns = [
  { key: 'lead', label: 'Lead' },
  { key: 'reserved', label: 'Reserved' },
  { key: 'contracted_won', label: 'Contracted/Won' },
  { key: 'lost', label: 'Lost' },
];

function formatPrice(value) {
  return new Intl.NumberFormat('en-US').format(value);
}
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-900">Pipeline</h1>
      <Link
        href="/deals/create"
        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition"
      >
        New Deal
      </Link>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div v-for="column in columns" :key="column.key" class="bg-slate-100/60 rounded-xl p-3">
        <div class="flex items-center justify-between px-2 mb-3">
          <h2 class="text-sm font-semibold text-slate-700">{{ column.label }}</h2>
          <span class="text-xs font-medium text-slate-400">{{ (dealsByStage[column.key] ?? []).length }}</span>
        </div>

        <div class="space-y-2">
          <Link
            v-for="deal in dealsByStage[column.key]"
            :key="deal.id"
            :href="`/deals/${deal.id}`"
            class="block bg-white rounded-lg p-3 shadow-sm border border-slate-200 hover:border-blue-300 hover:shadow transition"
          >
            <p class="text-sm font-semibold text-slate-800">{{ deal.contact?.name }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ deal.unit?.project?.name }} &middot; {{ deal.unit?.type }}</p>
            <p class="text-xs font-medium text-slate-600 mt-1">{{ formatPrice(deal.full_price) }}</p>
          </Link>
          <p v-if="!dealsByStage[column.key] || dealsByStage[column.key].length === 0" class="text-xs text-slate-400 px-2">
            No deals
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
