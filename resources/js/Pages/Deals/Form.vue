<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  deal: { type: Object, default: null },
  contacts: { type: Array, default: () => [] },
  units: { type: Array, default: () => [] },
  preselectedContactId: { type: Number, default: null },
  preselectedUnitId: { type: Number, default: null },
});

const stages = [
  { value: 'lead', label: 'Lead' },
  { value: 'reserved', label: 'Reserved' },
  { value: 'contracted_won', label: 'Contracted/Won' },
  { value: 'lost', label: 'Lost' },
];

const form = useForm({
  contact_id: props.deal?.contact_id ?? props.preselectedContactId ?? null,
  unit_id: props.deal?.unit_id ?? props.preselectedUnitId ?? null,
  full_price: props.deal?.full_price ?? '',
  deposit_amount: props.deal?.deposit_amount ?? '',
  deposit_paid_at: props.deal?.deposit_paid_at ?? '',
  stage: props.deal?.stage ?? 'lead',
});

function unitLabel(unit) {
  const price = new Intl.NumberFormat('en-US').format(unit.price);
  const status = unit.status !== 'available' ? ` — ${unit.status}` : '';

  return `${unit.project?.name} — ${unit.type} — ${price}${status}`;
}

// FR-010: a sold Unit accepts no *new* Deals, but an existing Deal on a
// since-sold Unit stays editable (e.g. to close it out as Lost).
const selectedUnit = computed(() => props.units.find((unit) => unit.id === form.unit_id) ?? null);

const blockedBySoldUnit = computed(() => !props.deal && selectedUnit.value?.status === 'sold');

function isUnitSelectable(unit) {
  return Boolean(props.deal) || unit.status !== 'sold';
}

function submit() {
  if (blockedBySoldUnit.value) {
    return;
  }

  if (props.deal) {
    form.put(`/deals/${props.deal.id}`);
  } else {
    form.post('/deals');
  }
}
</script>

<template>
  <AppLayout>
    <div class="max-w-md">
      <h1 class="text-2xl font-bold text-slate-900 mb-6">{{ deal ? 'Edit' : 'New' }} Deal</h1>

      <div class="bg-white border border-slate-200 rounded-xl p-6">
        <form class="space-y-5" @submit.prevent="submit">
          <div>
            <label for="contact_id" class="block text-sm font-medium text-slate-700 mb-1">Contact</label>
            <select
              id="contact_id"
              v-model="form.contact_id"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            >
              <option :value="null" disabled>Select a contact&hellip;</option>
              <option v-for="contact in contacts" :key="contact.id" :value="contact.id">
                {{ contact.name }}
              </option>
            </select>
            <p v-if="form.errors.contact_id" class="mt-1 text-sm text-rose-600">{{ form.errors.contact_id }}</p>
          </div>

          <div>
            <label for="unit_id" class="block text-sm font-medium text-slate-700 mb-1">Unit</label>
            <select
              id="unit_id"
              v-model="form.unit_id"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            >
              <option :value="null" disabled>Select a unit&hellip;</option>
              <option
                v-for="unit in units"
                :key="unit.id"
                :value="unit.id"
                :disabled="!isUnitSelectable(unit)"
              >
                {{ unitLabel(unit) }}
              </option>
            </select>
            <p v-if="form.errors.unit_id" class="mt-1 text-sm text-rose-600">{{ form.errors.unit_id }}</p>
            <p v-else-if="blockedBySoldUnit" class="mt-1 text-sm text-rose-600">
              This unit is already sold; no new deals can be opened on it.
            </p>
          </div>

          <div>
            <label for="full_price" class="block text-sm font-medium text-slate-700 mb-1">Full price</label>
            <input
              id="full_price"
              v-model="form.full_price"
              type="number"
              step="0.01"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
            <p v-if="form.errors.full_price" class="mt-1 text-sm text-rose-600">{{ form.errors.full_price }}</p>
          </div>

          <div>
            <label for="deposit_amount" class="block text-sm font-medium text-slate-700 mb-1">Deposit amount (optional)</label>
            <input
              id="deposit_amount"
              v-model="form.deposit_amount"
              type="number"
              step="0.01"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
            <p v-if="form.errors.deposit_amount" class="mt-1 text-sm text-rose-600">{{ form.errors.deposit_amount }}</p>
          </div>

          <div>
            <label for="deposit_paid_at" class="block text-sm font-medium text-slate-700 mb-1">Deposit date (optional)</label>
            <input
              id="deposit_paid_at"
              v-model="form.deposit_paid_at"
              type="date"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
            <p v-if="form.errors.deposit_paid_at" class="mt-1 text-sm text-rose-600">{{ form.errors.deposit_paid_at }}</p>
          </div>

          <div>
            <label for="stage" class="block text-sm font-medium text-slate-700 mb-1">Stage</label>
            <select
              id="stage"
              v-model="form.stage"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            >
              <option v-for="stage in stages" :key="stage.value" :value="stage.value">{{ stage.label }}</option>
            </select>
            <p v-if="form.errors.stage" class="mt-1 text-sm text-rose-600">{{ form.errors.stage }}</p>
          </div>

          <div v-if="blockedBySoldUnit" class="rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
            <span class="font-semibold">Unit already sold.</span>
            Another deal on this unit has been contracted/won. Pick a different unit to open a new deal.
          </div>

          <button
            type="submit"
            :disabled="form.processing || blockedBySoldUnit"
            class="inline-flex justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition disabled:opacity-50"
          >
            Save
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
