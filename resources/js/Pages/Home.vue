<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppButton from '@/Components/AppButton.vue';

/*
 * The public landing at `/`, also where login redirects. It sits outside the shell
 * because guests can reach it — tests/Feature/ExampleTest.php asserts a 200 here for
 * an unauthenticated visitor, so it must never move behind auth.
 *
 * NOTE ON COPY: this screen's Arabic text is deliberately left in place. Translating it
 * to English (FR-005) is task T024, which is BLOCKED pending the requester's decision
 * (research.md #12). Until then FR-005 and FR-044 are knowingly unmet on this one
 * screen. The dir="rtl" below is an interim rendering fix so the held copy displays
 * correctly under the document's dir="ltr" — remove it when T024 runs.
 */
const page = usePage();

const signedIn = computed(() => Boolean(page.props.auth?.user));

const action = computed(() => (signedIn.value
  ? { href: '/deals', label: 'ابدأ الآن' }
  : { href: '/login', label: 'تسجيل الدخول' }));
</script>

<template>
  <div class="min-h-screen bg-surface flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md rounded-panel border border-line bg-surface-raised p-10 text-center">
      <div dir="rtl">
        <h1 class="text-page-title font-bold text-ink-strong">أهلاً بيك في الـ CRM</h1>
        <p class="mt-2 text-body text-ink-muted">نظام إدارة العملاء الخاص بيك</p>
      </div>

      <div class="mt-8">
        <AppButton :href="action.href">{{ action.label }}</AppButton>
      </div>
    </div>
  </div>
</template>
