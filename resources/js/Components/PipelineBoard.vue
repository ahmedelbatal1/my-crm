<script setup>
import { computed } from 'vue';
import DealCard from '@/Components/DealCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { money, number } from '@/lib/format.js';

/*
 * The Deals pipeline as one column per stage (FR-028), showing each column's label, its
 * count, and the total value of the deals in it — all computed from the rows the page
 * already receives, so no new data is loaded.
 *
 * READ-ONLY BY CONTRACT (FR-029). This component must never gain a drag handler, an
 * "advance stage" control, or any other write path; opening a Deal is the only route to
 * changing its stage. tests/Unit/DesignSystemFitnessTest.php enforces that statically.
 */
const props = defineProps({
  dealsByStage: { type: Object, required: true },
});

const stages = [
  { key: 'lead', label: 'Lead' },
  { key: 'reserved', label: 'Reserved' },
  { key: 'contracted_won', label: 'Contracted / Won' },
  { key: 'lost', label: 'Lost' },
];

const columns = computed(() => stages.map((stage) => {
  const deals = props.dealsByStage[stage.key] ?? [];

  return {
    ...stage,
    deals,
    count: deals.length,
    total: deals.reduce((sum, deal) => sum + Number(deal.full_price ?? 0), 0),
  };
}));

const isEmpty = computed(() => columns.value.every((column) => column.count === 0));
</script>

<template>
  <EmptyState
    v-if="isEmpty"
    title="Your pipeline is empty"
    description="Deals you open will appear here, grouped by the stage they have reached."
  >
    <template #action>
      <slot name="empty-action" />
    </template>
  </EmptyState>

  <!--
    minmax(0, 1fr) rather than plain 1fr: without the zero minimum a single long word
    blows out one column and squeezes the other three (FR-030). Below the 768px floor
    the row scrolls sideways instead of crushing the columns (FR-012).
  -->
  <div v-else class="-mx-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
    <div class="grid min-w-[48rem] grid-cols-[repeat(4,minmax(0,1fr))] gap-4">
      <section
        v-for="column in columns"
        :key="column.key"
        class="rounded-panel bg-surface-sunken p-3"
      >
        <header class="mb-3 px-1">
          <div class="flex items-baseline justify-between gap-2">
            <h2 class="truncate text-body font-semibold text-ink">{{ column.label }}</h2>
            <span class="shrink-0 text-support font-medium tabular text-ink-muted">
              {{ number(column.count) }}
            </span>
          </div>
          <p class="mt-0.5 text-support tabular text-ink-muted">{{ money(column.total) }} EGP</p>
        </header>

        <!-- A heavy column scrolls inside itself, leaving its siblings' widths alone. -->
        <div class="max-h-[32rem] space-y-2 overflow-y-auto">
          <DealCard v-for="deal in column.deals" :key="deal.id" :deal="deal" />

          <p v-if="column.count === 0" class="px-1 py-2 text-support text-ink-muted">
            No deals at this stage
          </p>
        </div>
      </section>
    </div>
  </div>
</template>
