<script setup>
defineProps({
  status: { type: String, required: true },
});

const styles = {
  available: 'bg-emerald-100 text-emerald-800',
  reserved: 'bg-amber-100 text-amber-800',
  sold: 'bg-slate-800 text-white',
};

const labels = {
  available: 'Available',
  reserved: 'Reserved',
  sold: 'Sold',
};
</script>

<template>
  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" :class="styles[status]">
    {{ labels[status] ?? status }}
  </span>
</template>
