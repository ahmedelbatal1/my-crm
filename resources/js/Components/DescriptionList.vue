<script setup>
import { formatters, ABSENT } from '@/lib/format.js';

/*
 * The shared field/value pattern for detail screens (FR-031). Reuses the same
 * formatters the tables use, so a price reads identically in both places.
 *
 * Item shape: { label, value, format?, hint? } — `hint` carries the unit marker
 * ("EGP", "m²") so it is named once beside the label, never repeated per value (FR-023).
 */
defineProps({
  items: { type: Array, required: true },
});

function display(item) {
  if (item.format && formatters[item.format]) {
    return formatters[item.format](item.value);
  }

  return item.value === null || item.value === undefined || item.value === '' ? ABSENT : item.value;
}
</script>

<template>
  <dl class="divide-y divide-line">
    <div v-for="item in items" :key="item.label" class="flex items-baseline justify-between gap-4 py-3">
      <dt class="text-body text-ink-muted">
        {{ item.label }}<span v-if="item.hint" class="text-support"> ({{ item.hint }})</span>
      </dt>
      <dd class="text-body font-medium text-ink" :class="item.format ? 'tabular' : ''">
        {{ display(item) }}
      </dd>
    </div>
  </dl>
</template>
