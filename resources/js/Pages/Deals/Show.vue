<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StageBadge from '@/Components/StageBadge.vue';

defineProps({
  deal: { type: Object, required: true },
});

function formatPrice(value) {
  return new Intl.NumberFormat('en-US').format(value);
}
</script>

<template>
  <AppLayout>
    <div class="max-w-xl bg-white border border-slate-200 rounded-xl p-6">
      <div class="flex items-center justify-between mb-1">
        <h1 class="text-2xl font-bold text-slate-900">{{ deal.contact?.name }}</h1>
        <Link :href="`/deals/${deal.id}/edit`" class="text-sm font-medium text-blue-600 hover:underline">
          Edit
        </Link>
      </div>

      <div class="mb-6">
        <StageBadge :stage="deal.stage" />
      </div>

      <dl class="space-y-3 text-sm">
        <div class="flex justify-between">
          <dt class="text-slate-500">Unit</dt>
          <dd class="font-medium text-slate-800">{{ deal.unit?.project?.name }} &mdash; {{ deal.unit?.type }}</dd>
        </div>
        <div class="flex justify-between">
          <dt class="text-slate-500">Full price</dt>
          <dd class="font-medium text-slate-800">{{ formatPrice(deal.full_price) }}</dd>
        </div>
        <div v-if="deal.deposit_amount" class="flex justify-between">
          <dt class="text-slate-500">Deposit</dt>
          <dd class="font-medium text-slate-800">
            {{ formatPrice(deal.deposit_amount) }}
            <span v-if="deal.deposit_paid_at" class="text-slate-400 font-normal">on {{ deal.deposit_paid_at }}</span>
          </dd>
        </div>
      </dl>
    </div>
  </AppLayout>
</template>
