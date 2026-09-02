<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: null },
  eyebrow: { type: String, default: null },
});
</script>

<template>
  <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
      <p v-if="eyebrow" class="text-support font-medium text-ink-muted">{{ eyebrow }}</p>
      <h1 class="text-page-title font-bold text-ink-strong">{{ title }}</h1>
      <p v-if="description" class="mt-1 text-body text-ink-muted">{{ description }}</p>
    </div>

    <div class="shrink-0">
      <slot name="action" />
    </div>
  </header>
</template>
