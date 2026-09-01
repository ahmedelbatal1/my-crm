<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
  project: { type: Object, required: true },
  units: { type: Array, required: true },
});

function formatPrice(value) {
  return new Intl.NumberFormat('en-US').format(value);
}
</script>

<template>
  <AppLayout>
    <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">{{ project.name }}</h1>
        <Link :href="`/projects/${project.id}/edit`" class="text-sm font-medium text-blue-600 hover:underline">
          Edit
        </Link>
      </div>
      <p v-if="project.location" class="text-sm text-slate-500 mt-1">{{ project.location }}</p>
      <p v-if="project.description" class="text-sm text-slate-500 mt-1">{{ project.description }}</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold text-slate-900">Units</h2>
        <Link
          :href="`/projects/${project.id}/units/create`"
          class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition"
        >
          New Unit
        </Link>
      </div>

      <div class="divide-y divide-slate-100">
        <Link
          v-for="unit in units"
          :key="unit.id"
          :href="`/units/${unit.id}/edit`"
          class="flex items-center justify-between py-3 hover:bg-slate-50 -mx-2 px-2 rounded-lg transition"
        >
          <div>
            <p class="text-sm font-medium text-slate-800 capitalize">{{ unit.type }}</p>
            <p class="text-xs text-slate-500">{{ unit.area }} m&sup2; &middot; {{ formatPrice(unit.price) }}</p>
          </div>
          <StatusBadge :status="unit.status" />
        </Link>
        <p v-if="units.length === 0" class="py-6 text-center text-sm text-slate-400">
          No units yet.
        </p>
      </div>
    </div>
  </AppLayout>
</template>
