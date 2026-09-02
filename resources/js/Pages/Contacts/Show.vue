<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import DataTable from '@/Components/DataTable.vue';
import DescriptionList from '@/Components/DescriptionList.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ConfirmAction from '@/Components/ConfirmAction.vue';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  contact: { type: Object, required: true },
});

const details = computed(() => [
  { label: 'Phone', value: props.contact.phone },
  { label: 'Email', value: props.contact.email },
  { label: 'Company', value: props.contact.company?.name ?? 'Individual buyer' },
]);

// FR-038: the deals are already on the page, so a contact that has any says so.
const dealCount = computed(() => (props.contact.deals ?? []).length);

function destroy() {
  router.delete(`/contacts/${props.contact.id}`);
}

const columns = [
  { key: 'unit.project.name', label: 'Project' },
  { key: 'unit.type', label: 'Unit' },
  { key: 'full_price', label: 'Full price (EGP)', align: 'right', format: 'money' },
  { key: 'stage', label: 'Stage' },
];
</script>

<template>
  <AppLayout>
    <PageHeader :title="contact.name">
      <template #action>
        <div class="flex items-center gap-2">
          <AppButton variant="secondary" :href="`/contacts/${contact.id}/edit`">Edit</AppButton>
          <ConfirmAction
            :disabled="dealCount > 0"
            :reason="dealCount > 0 ? `has ${dealCount} deal(s)` : null"
            :question="`Delete ${contact.name}?`"
            @confirmed="destroy"
          />
        </div>
      </template>
    </PageHeader>

    <div class="mb-6 rounded-panel border border-line bg-surface-raised px-6 py-2">
      <DescriptionList :items="details" />
    </div>

    <section>
      <div class="mb-4 flex items-center justify-between gap-4">
        <h2 class="text-section font-semibold text-ink-strong">Deals</h2>
        <AppButton :href="`/deals/create?contact_id=${contact.id}`">New Deal</AppButton>
      </div>

      <DataTable
        :columns="columns"
        :rows="contact.deals ?? []"
        :row-href="(deal) => `/deals/${deal.id}`"
        empty-title="No deals for this contact yet"
        empty-description="Open a deal to link this contact to a unit and start tracking their progress."
      >
        <template #cell:unit.type="{ row }">
          <span class="capitalize">{{ row.unit?.type }}</span>
        </template>

        <template #cell:stage="{ row }">
          <StatusBadge :value="row.stage" kind="stage" />
        </template>

        <template #empty-action>
          <AppButton :href="`/deals/create?contact_id=${contact.id}`">New Deal</AppButton>
        </template>
      </DataTable>
    </section>
  </AppLayout>
</template>
