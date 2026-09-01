<script setup>
defineProps({
  stage: { type: String, required: true },
});

const styles = {
  lead: 'bg-slate-100 text-slate-700',
  reserved: 'bg-amber-100 text-amber-800',
  contracted_won: 'bg-emerald-100 text-emerald-800',
  lost: 'bg-rose-100 text-rose-700',
};

const labels = {
  lead: 'Lead',
  reserved: 'Reserved',
  contracted_won: 'Contracted/Won',
  lost: 'Lost',
};
</script>

<template>
  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" :class="styles[stage]">
    {{ labels[stage] ?? stage }}
  </span>
</template>
