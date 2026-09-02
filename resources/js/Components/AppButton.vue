<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  variant: { type: String, default: 'primary' },
  type: { type: String, default: 'button' },
  href: { type: String, default: null },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

const variants = {
  primary: 'bg-primary text-white hover:bg-primary-hover',
  secondary: 'bg-surface-raised text-ink border border-line-strong hover:bg-surface-sunken',
  danger: 'bg-brick-800 text-white hover:bg-brick-900',
  ghost: 'text-primary-text hover:bg-surface-sunken',
};

// FR-035: an in-flight submission must not be triggerable a second time.
const isInert = computed(() => props.disabled || props.loading);

const classes = computed(() => [
  'inline-flex items-center justify-center gap-2 rounded-control px-4 py-2 text-body font-semibold',
  'transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus',
  variants[props.variant] ?? variants.primary,
  isInert.value ? 'opacity-60 pointer-events-none' : '',
]);
</script>

<template>
  <Link v-if="href && !isInert" :href="href" :class="classes">
    <slot />
  </Link>

  <span v-else-if="href" :class="classes" aria-disabled="true">
    <slot />
  </span>

  <button v-else :type="type" :disabled="isInert" :class="classes">
    <span v-if="loading" class="size-3 rounded-pill border-2 border-current border-t-transparent animate-spin" aria-hidden="true" />
    <slot />
    <span v-if="loading" class="sr-only">Saving…</span>
  </button>
</template>
