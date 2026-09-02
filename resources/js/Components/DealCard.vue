<script setup>
import { Link } from '@inertiajs/vue3';
import { money } from '@/lib/format.js';

/*
 * One deal on the pipeline board. Read-only by contract (FR-029) — the whole card is a
 * link to the deal, and it carries no control that could write a stage.
 */
defineProps({
  deal: { type: Object, required: true },
});
</script>

<template>
  <Link
    :href="`/deals/${deal.id}`"
    class="block rounded-control border border-line bg-surface-raised p-3 transition hover:border-primary"
  >
    <!-- FR-027: long names truncate rather than reflowing the card. -->
    <p class="truncate text-body font-semibold text-ink">{{ deal.contact?.name }}</p>
    <p class="mt-0.5 truncate text-support text-ink-muted">
      {{ deal.unit?.project?.name }} &middot; {{ deal.unit?.type }}
    </p>
    <p class="mt-1 text-support font-medium tabular text-ink-muted">{{ money(deal.full_price) }}</p>
  </Link>
</template>
