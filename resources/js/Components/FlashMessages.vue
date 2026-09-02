<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

/*
 * FR-010 / FR-039: the shell's one message region. All three severities are supported;
 * this feature emits none of the success kind, because producing one needs a controller
 * change FR-043 forbids (research.md #8). The severity is wired so a later feature can
 * flash success with no frontend work.
 *
 * Colours come from the four status families — palm/ochre/brick — not from a parallel
 * success/warning/danger palette (FR-002).
 */
const severities = [
  { key: 'success', classes: 'bg-palm-100 text-palm-900', role: 'status' },
  { key: 'warning', classes: 'bg-ochre-100 text-ochre-900', role: 'status' },
  { key: 'error', classes: 'bg-brick-100 text-brick-900', role: 'alert' },
];

const messages = computed(() => severities
  .map((severity) => ({ ...severity, text: page.props.flash?.[severity.key] }))
  .filter((severity) => Boolean(severity.text)));
</script>

<template>
  <div v-if="messages.length" class="mb-6 space-y-2">
    <p
      v-for="message in messages"
      :key="message.key"
      :role="message.role"
      class="rounded-control px-4 py-3 text-body font-medium"
      :class="message.classes"
    >
      {{ message.text }}
    </p>
  </div>
</template>
