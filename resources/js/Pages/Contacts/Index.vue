<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  contacts: { type: Array, required: true },
});
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-900">Contacts</h1>
      <Link
        href="/contacts/create"
        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition"
      >
        New Contact
      </Link>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl divide-y divide-slate-100 overflow-hidden">
      <Link
        v-for="contact in contacts"
        :key="contact.id"
        :href="`/contacts/${contact.id}`"
        class="block px-5 py-4 hover:bg-slate-50 transition"
      >
        <p class="text-sm font-semibold text-slate-800">{{ contact.name }}</p>
        <p class="text-sm text-slate-500">{{ contact.phone }} &middot; {{ contact.company?.name ?? 'Individual buyer' }}</p>
      </Link>
      <p v-if="contacts.length === 0" class="px-5 py-8 text-center text-sm text-slate-400">
        No contacts yet.
      </p>
    </div>
  </AppLayout>
</template>
