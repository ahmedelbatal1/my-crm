<script setup>
import { Link, router } from '@inertiajs/vue3';

function logout() {
  router.post('/logout');
}

const navItems = [
  { href: '/deals', label: 'Pipeline' },
  { href: '/contacts', label: 'Contacts' },
  { href: '/projects', label: 'Projects' },
  { href: '/companies', label: 'Companies' },
];
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200">
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-10">
          <Link href="/deals" class="text-lg font-bold text-slate-900">My CRM</Link>
          <div class="flex gap-1">
            <Link
              v-for="item in navItems"
              :key="item.href"
              :href="item.href"
              class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition"
            >
              {{ item.label }}
            </Link>
          </div>
        </div>
        <button
          type="button"
          class="text-sm font-medium text-slate-500 hover:text-red-600 transition"
          @click="logout"
        >
          Log out
        </button>
      </div>
    </nav>

    <main class="max-w-6xl mx-auto px-6 py-10">
      <slot />
    </main>
  </div>
</template>
