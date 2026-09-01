<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  projects: { type: Array, required: true },
});
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-900">Projects</h1>
      <Link
        href="/projects/create"
        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition"
      >
        New Project
      </Link>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <Link
        v-for="project in projects"
        :key="project.id"
        :href="`/projects/${project.id}`"
        class="block bg-white border border-slate-200 rounded-xl p-5 hover:border-blue-300 hover:shadow-sm transition"
      >
        <p class="text-base font-semibold text-slate-800">{{ project.name }}</p>
        <p v-if="project.location" class="text-sm text-slate-500 mt-0.5">{{ project.location }}</p>
        <p class="text-xs font-medium text-slate-400 mt-3">{{ project.units_count }} unit(s)</p>
      </Link>
      <p v-if="projects.length === 0" class="text-sm text-slate-400 col-span-2 text-center py-8">
        No projects yet.
      </p>
    </div>
  </AppLayout>
</template>
