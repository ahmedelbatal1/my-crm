<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ConfirmAction from '@/Components/ConfirmAction.vue';

const props = defineProps({
  project: { type: Object, required: true },
  units: { type: Array, required: true },
});

// FR-038: the units are on the page, so a project that still holds them says so.
const unitCount = computed(() => props.units.length);

function destroyProject() {
  router.delete(`/projects/${props.project.id}`);
}

/*
 * A unit's deals are NOT sent to this screen, so a unit that has deals cannot be
 * pre-empted here — adding the count would mean editing ProjectController, which FR-043
 * forbids. A blocked attempt lands on the styled 403 page instead (FR-037, FR-041).
 */
function destroyUnit(unit) {
  router.delete(`/units/${unit.id}`);
}

/*
 * FR-023: the currency and the unit of measure are named ONCE, in the column header —
 * never repeated in every cell. FR-021/FR-006: both are right-aligned with equal-width
 * digits so the column aligns on the decimal separator.
 */
const columns = [
  { key: 'type', label: 'Type' },
  { key: 'area', label: 'Area (m²)', align: 'right', format: 'area' },
  { key: 'price', label: 'Price (EGP)', align: 'right', format: 'money' },
  { key: 'status', label: 'Availability' },
];
</script>

<template>
  <AppLayout>
    <PageHeader :title="project.name" :description="project.location">
      <template #action>
        <div class="flex items-center gap-2">
          <AppButton variant="secondary" :href="`/projects/${project.id}/edit`">Edit</AppButton>
          <ConfirmAction
            :disabled="unitCount > 0"
            :reason="unitCount > 0 ? `holds ${unitCount} unit(s)` : null"
            :question="`Delete ${project.name}?`"
            @confirmed="destroyProject"
          />
        </div>
      </template>
    </PageHeader>

    <p v-if="project.description" class="mb-6 max-w-2xl text-body text-ink-muted">{{ project.description }}</p>

    <section>
      <div class="mb-4 flex items-center justify-between gap-4">
        <h2 class="text-section font-semibold text-ink-strong">Units</h2>
        <AppButton :href="`/projects/${project.id}/units/create`">New Unit</AppButton>
      </div>

      <DataTable
        :columns="columns"
        :rows="units"
        :row-href="(unit) => `/units/${unit.id}/edit`"
        empty-title="No units in this project yet"
        empty-description="Add the apartments, villas or shops this project contains. Each starts out available."
      >
        <template #cell:type="{ row }">
          <span class="capitalize">{{ row.type }}</span>
        </template>

        <template #cell:status="{ row }">
          <StatusBadge :value="row.status" kind="availability" />
        </template>

        <template #actions="{ row }">
          <ConfirmAction :question="`Delete this ${row.type}?`" @confirmed="destroyUnit(row)" />
        </template>

        <template #empty-action>
          <AppButton :href="`/projects/${project.id}/units/create`">New Unit</AppButton>
        </template>
      </DataTable>
    </section>
  </AppLayout>
</template>
