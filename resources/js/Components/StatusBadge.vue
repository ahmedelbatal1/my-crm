<script setup>
import { computed } from 'vue';
import { humanise } from '@/lib/format.js';

/*
 * The single status indicator for all seven Deal-stage and Unit-availability values
 * (FR-015). Specified in specs/003-design-system-shell/data-model.md §2. Replaces the
 * former StageBadge + StatusBadge pair, whose overlapping amber treatments made a
 * Reserved deal and a Reserved unit indistinguishable.
 *
 * `kind` drives SHAPE, not just colour, so the two kinds stay tellable apart in
 * greyscale and for colour-blind readers (FR-017, FR-018):
 *   stage        -> pill with a leading dot
 *   availability -> square-cornered bordered tag, no dot
 */
const props = defineProps({
  value: { type: String, required: true },
  kind: {
    type: String,
    required: true,
    validator: (kind) => ['stage', 'availability'].includes(kind),
  },
});

const families = {
  quiet: 'bg-quiet-100 text-quiet-800',
  ochre: 'bg-ochre-100 text-ochre-800',
  palm: 'bg-palm-100 text-palm-800',
  brick: 'bg-brick-100 text-brick-800',
  ink: 'bg-ink-800 text-white',
};

const stages = {
  lead: { label: 'Lead', family: 'quiet' },
  reserved: { label: 'Reserved', family: 'ochre' },
  contracted_won: { label: 'Contracted / Won', family: 'palm' },
  lost: { label: 'Lost', family: 'brick' },
};

const availabilities = {
  available: { label: 'Available', family: 'palm' },
  reserved: { label: 'Reserved', family: 'ochre' },
  sold: { label: 'Sold', family: 'ink' },
};

// FR-019: an unrecognised value degrades to a readable quiet badge, never a blank one.
const entry = computed(() => {
  const table = props.kind === 'stage' ? stages : availabilities;

  return table[props.value] ?? { label: humanise(props.value), family: 'quiet' };
});

const kindLabel = computed(() => (props.kind === 'stage' ? 'Deal stage' : 'Unit availability'));

const shape = computed(() => (props.kind === 'stage'
  ? 'rounded-pill'
  : 'rounded-control border border-current/25'));
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-support font-semibold whitespace-nowrap"
    :class="[families[entry.family], shape]"
    :title="`${kindLabel}: ${entry.label}`"
  >
    <span v-if="kind === 'stage'" class="size-1.5 rounded-pill bg-current" aria-hidden="true" />
    <span class="sr-only">{{ kindLabel }}:</span>
    {{ entry.label }}
  </span>
</template>
