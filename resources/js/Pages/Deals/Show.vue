<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import DescriptionList from '@/Components/DescriptionList.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
  deal: { type: Object, required: true },
});

/*
 * FR-023: "EGP" is named once per field via the hint, not repeated in the value.
 * FR-025: an unrecorded deposit renders as an em dash, which is visibly different from
 * a deposit recorded as 0.00 — that distinction is the whole point of the rule.
 */
const details = computed(() => [
  {
    label: 'Unit',
    value: `${props.deal.unit?.project?.name ?? '—'} — ${props.deal.unit?.type ?? '—'}`,
  },
  { label: 'Full price', value: props.deal.full_price, format: 'money', hint: 'EGP' },
  { label: 'Deposit', value: props.deal.deposit_amount, format: 'money', hint: 'EGP' },
  { label: 'Deposit paid', value: props.deal.deposit_paid_at, format: 'date' },
]);
</script>

<template>
  <AppLayout>
    <div class="max-w-xl">
      <PageHeader :title="deal.contact?.name">
        <template #action>
          <AppButton variant="secondary" :href="`/deals/${deal.id}/edit`">Edit</AppButton>
        </template>
      </PageHeader>

      <div class="mb-6">
        <StatusBadge :value="deal.stage" kind="stage" />
      </div>

      <div class="rounded-panel border border-line bg-surface-raised px-6 py-2">
        <DescriptionList :items="details" />
      </div>
    </div>
  </AppLayout>
</template>
