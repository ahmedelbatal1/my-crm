<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FlashMessages from '@/Components/FlashMessages.vue';

const page = usePage();

const navItems = [
  { href: '/deals', label: 'Pipeline' },
  { href: '/contacts', label: 'Contacts' },
  { href: '/projects', label: 'Projects' },
  { href: '/companies', label: 'Companies' },
];

const roleLabels = {
  admin: 'Admin',
  sales_rep: 'Sales rep',
};

const user = computed(() => page.props.auth?.user ?? null);

// FR-016: the raw role value never reaches the template.
const roleLabel = computed(() => roleLabels[user.value?.role] ?? null);

/*
 * FR-008: a nav item is current when the path equals its href or is nested beneath it.
 * The startsWith arm is what keeps "Projects" marked on /projects/5/units/create — an
 * exact-match check gets that case wrong.
 */
function isCurrent(href) {
  const path = page.url.split('?')[0];

  return path === href || path.startsWith(`${href}/`);
}

function logout() {
  router.post('/logout');
}
</script>

<template>
  <div class="min-h-screen bg-surface text-ink">
    <nav class="border-b border-line bg-surface-raised">
      <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
        <div class="flex min-w-0 items-center gap-6">
          <Link
            href="/deals"
            class="text-section font-bold text-ink-strong rounded-control focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
          >
            My CRM
          </Link>

          <div class="flex gap-1 overflow-x-auto">
            <Link
              v-for="item in navItems"
              :key="item.href"
              :href="item.href"
              :aria-current="isCurrent(item.href) ? 'page' : undefined"
              class="whitespace-nowrap rounded-control px-3 py-2 text-body font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
              :class="isCurrent(item.href)
                ? 'bg-primary text-white'
                : 'text-ink-muted hover:bg-surface-sunken hover:text-ink'"
            >
              {{ item.label }}
            </Link>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-4">
          <!-- FR-009: who you are and in what role, on every screen. -->
          <p v-if="user" class="hidden text-right sm:block">
            <span class="block text-body font-semibold text-ink">{{ user.name }}</span>
            <span class="block text-support text-ink-muted">{{ roleLabel }}</span>
          </p>

          <button
            type="button"
            class="rounded-control px-2 py-1 text-body font-medium text-ink-muted transition hover:text-primary-text focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
            @click="logout"
          >
            Log out
          </button>
        </div>
      </div>
    </nav>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
      <FlashMessages />
      <slot />
    </main>
  </div>
</template>
