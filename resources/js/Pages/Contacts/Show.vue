<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StageBadge from '@/Components/StageBadge.vue';

defineProps({
  contact: { type: Object, required: true },
});
</script>

<template>
  <AppLayout>
    <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">{{ contact.name }}</h1>
        <Link :href="`/contacts/${contact.id}/edit`" class="text-sm font-medium text-blue-600 hover:underline">
          Edit
        </Link>
      </div>
      <p class="text-sm text-slate-500 mt-1">
        {{ contact.phone }}<span v-if="contact.email"> &middot; {{ contact.email }}</span>
      </p>
      <p class="text-sm text-slate-500">{{ contact.company?.name ?? 'Individual buyer' }}</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold text-slate-900">Deals</h2>
        <Link
          :href="`/deals/create?contact_id=${contact.id}`"
          class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition"
        >
          New Deal
        </Link>
      </div>

      <div class="divide-y divide-slate-100">
        <Link
          v-for="deal in contact.deals"
          :key="deal.id"
          :href="`/deals/${deal.id}`"
          class="flex items-center justify-between py-3 hover:bg-slate-50 -mx-2 px-2 rounded-lg transition"
        >
          <span class="text-sm text-slate-700">{{ deal.unit?.project?.name }} &mdash; {{ deal.unit?.type }}</span>
          <StageBadge :stage="deal.stage" />
        </Link>
        <p v-if="!contact.deals || contact.deals.length === 0" class="py-6 text-center text-sm text-slate-400">
          No deals yet.
        </p>
      </div>
    </div>
  </AppLayout>
</template>
