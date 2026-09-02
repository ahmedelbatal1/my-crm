<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppButton from '@/Components/AppButton.vue';

const props = defineProps({
  status: { type: Number, required: true },
});

const page = usePage();

const signedIn = computed(() => Boolean(page.props.auth?.user));

/*
 * FR-041: each status gets plain-English copy explaining what happened, not a bare
 * code. The four blocked deletions arrive here as 403s when a screen could not
 * pre-empt them, so the 403 copy names dependent records as the usual cause (FR-037).
 */
const copy = {
  403: {
    title: 'You do not have access to that',
    body: 'It either belongs to another sales rep, or it is a record that cannot be '
      + 'changed while other records still depend on it — a project with units, a unit '
      + 'with deals, a contact with deals, or a company with contacts.',
  },
  404: {
    title: 'That page does not exist',
    body: 'The record may have been deleted, or the address may be mistyped.',
  },
  405: {
    title: 'That action is not available here',
    body: 'The page was reached in a way it does not support. Try again from the menu.',
  },
  419: {
    title: 'Your session expired',
    body: 'You were signed out after a period of inactivity. Sign in again and your work '
      + 'can continue — nothing was saved from the last attempt.',
  },
  500: {
    title: 'Something went wrong on our side',
    body: 'The problem has been logged. Try again in a moment.',
  },
};

const current = computed(() => copy[props.status] ?? {
  title: 'Something went wrong',
  body: 'An unexpected problem occurred.',
});

// A 419 means the session is gone, so the only useful route back is sign-in.
const returnTo = computed(() => (signedIn.value && props.status !== 419
  ? { href: '/deals', label: 'Back to the pipeline' }
  : { href: '/login', label: 'Go to sign in' }));
</script>

<template>
  <div class="min-h-screen bg-surface flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md text-center">
      <p class="text-support font-semibold tracking-widest text-ink-muted uppercase">Error {{ status }}</p>
      <h1 class="mt-3 text-page-title font-bold text-ink-strong">{{ current.title }}</h1>
      <p class="mt-3 text-body text-ink-muted">{{ current.body }}</p>

      <div class="mt-8">
        <AppButton :href="returnTo.href">{{ returnTo.label }}</AppButton>
      </div>
    </div>
  </div>
</template>
