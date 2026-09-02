<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ConfirmAction from '@/Components/ConfirmAction.vue';

defineProps({
  projects: { type: Array, required: true },
});

function destroy(project) {
  router.delete(`/projects/${project.id}`);
}

const columns = [
  { key: 'name', label: 'Project' },
  { key: 'location', label: 'Location' },
  { key: 'units_count', label: 'Units', align: 'right', format: 'number' },
];
</script>

<template>
  <AppLayout>
    <PageHeader title="Projects" description="Every project and its unit inventory.">
      <template #action>
        <AppButton href="/projects/create">New Project</AppButton>
      </template>
    </PageHeader>

    <DataTable
      :columns="columns"
      :rows="projects"
      :row-href="(project) => `/projects/${project.id}`"
      empty-title="No projects yet"
      empty-description="A project holds the units you sell. Create one, then add its units."
    >
      <!--
        FR-038: units_count is already on every row, so a project that still holds units
        says so instead of offering a delete that would fail.
      -->
      <template #actions="{ row }">
        <ConfirmAction
          :disabled="row.units_count > 0"
          :reason="row.units_count > 0 ? `holds ${row.units_count} unit(s)` : null"
          :question="`Delete ${row.name}?`"
          @confirmed="destroy(row)"
        />
      </template>

      <template #empty-action>
        <AppButton href="/projects/create">New Project</AppButton>
      </template>
    </DataTable>
  </AppLayout>
</template>
