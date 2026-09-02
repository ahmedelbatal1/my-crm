<script setup>
/*
 * One field treatment for every form (FR-034, FR-036).
 *
 * The border uses --color-line-strong, not the decorative --color-line: WCAG 1.4.11
 * requires 3:1 for the boundary of an interactive control, and the stock grey border
 * these forms used before measured about 1.6:1 (research.md #3). That is a real
 * accessibility fix, not a restyle.
 */
defineProps({
  label: { type: String, required: true },
  id: { type: String, required: true },
  error: { type: String, default: null },
  required: { type: Boolean, default: false },
  hint: { type: String, default: null },
});
</script>

<template>
  <div>
    <label :for="id" class="mb-1 block text-body font-medium text-ink">
      {{ label }}
      <span v-if="hint" class="font-normal text-ink-muted">({{ hint }})</span>
      <span v-if="required" class="text-brick-800" aria-hidden="true">*</span>
      <span v-else class="font-normal text-ink-muted">— optional</span>
    </label>

    <!--
      The control itself is passed in. Styling it from here keeps every input, select and
      textarea in the app identical without each page restating the classes.
    -->
    <div
      class="[&>input]:w-full [&>select]:w-full [&>textarea]:w-full"
      :class="error
        ? '[&>*]:border-brick-800 [&>*]:bg-brick-100/40'
        : '[&>*]:border-line-strong'"
    >
      <slot :described-by="error ? `${id}-error` : undefined" />
    </div>

    <p v-if="error" :id="`${id}-error`" class="mt-1 text-body text-brick-800">{{ error }}</p>
  </div>
</template>

<style scoped>
/*
 * Applied here rather than as utility classes on every control, so a new form cannot
 * accidentally ship an unstyled or low-contrast input.
 */
:deep(input),
:deep(select),
:deep(textarea) {
  display: block;
  border-radius: var(--radius-control);
  border-width: 1px;
  padding: 0.5rem 0.75rem;
  font-size: var(--text-body);
  background-color: var(--color-surface-raised);
  color: var(--color-ink);
}
</style>
