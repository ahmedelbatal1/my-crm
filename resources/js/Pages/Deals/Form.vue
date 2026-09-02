<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import FormField from '@/Components/FormField.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { money } from '@/lib/format.js';

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
  { value: 'contracted_won', label: 'Contracted / Won' },
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
  return `${unit.project?.name} — ${unit.type} — ${money(unit.price)} EGP`;
}

// FR-010 (feature 002): a sold Unit accepts no *new* Deals, but an existing Deal on a
// since-sold Unit stays editable, e.g. to close it out as Lost.
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
      <PageHeader :title="`${deal ? 'Edit' : 'New'} Deal`" />

      <div class="rounded-panel border border-line bg-surface-raised p-6">
        <form class="space-y-5" @submit.prevent="submit">
          <FormField id="contact_id" label="Contact" required :error="form.errors.contact_id">
            <select id="contact_id" v-model="form.contact_id">
              <option :value="null" disabled>Select a contact&hellip;</option>
              <option v-for="contact in contacts" :key="contact.id" :value="contact.id">
                {{ contact.name }}
              </option>
            </select>
          </FormField>

          <FormField id="unit_id" label="Unit" required :error="form.errors.unit_id">
            <select id="unit_id" v-model="form.unit_id">
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
          </FormField>

          <!--
            FR-042: the selected unit's availability is shown through the shared status
            indicator, and a sold unit blocks the action up front with its reason stated
            (FR-038) — replacing feature 002's hand-styled banner.
          -->
          <div v-if="selectedUnit" class="flex flex-wrap items-center gap-2">
            <span class="text-body text-ink-muted">Selected unit</span>
            <StatusBadge :value="selectedUnit.status" kind="availability" />
            <span v-if="blockedBySoldUnit" class="text-body text-brick-900">
              Another deal on this unit reached Contracted / Won, so no new deal can be
              opened on it. Pick a different unit.
            </span>
          </div>

          <FormField id="full_price" label="Full price" required hint="EGP" :error="form.errors.full_price">
            <input id="full_price" v-model="form.full_price" type="number" step="0.01" />
          </FormField>

          <FormField id="deposit_amount" label="Deposit amount" hint="EGP" :error="form.errors.deposit_amount">
            <input id="deposit_amount" v-model="form.deposit_amount" type="number" step="0.01" />
          </FormField>

          <FormField id="deposit_paid_at" label="Deposit date" :error="form.errors.deposit_paid_at">
            <input id="deposit_paid_at" v-model="form.deposit_paid_at" type="date" />
          </FormField>

          <FormField id="stage" label="Stage" required :error="form.errors.stage">
            <select id="stage" v-model="form.stage">
              <option v-for="stage in stages" :key="stage.value" :value="stage.value">{{ stage.label }}</option>
            </select>
          </FormField>

          <AppButton type="submit" :loading="form.processing" :disabled="blockedBySoldUnit">
            Save
          </AppButton>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
