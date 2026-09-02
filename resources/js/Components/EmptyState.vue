<script setup>
/*
 * FR-032: a list with no records explains what belongs there and offers the action that
 * creates the first one — never a bare blank area.
 *
 * FR-033: for ownership-scoped lists the calling page passes copy naming the scope
 * ("None of your contacts yet"), so a rep is not told the whole system is empty.
 */
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: null },
});
</script>

<template>
  <div class="rounded-panel border border-dashed border-line-strong bg-surface-raised px-6 py-12 text-center">
    <p class="text-section font-semibold text-ink-strong">{{ title }}</p>
    <p v-if="description" class="mx-auto mt-2 max-w-sm text-body text-ink-muted">{{ description }}</p>

    <div class="mt-6">
      <slot name="action" />
    </div>
  </div>
</template>
