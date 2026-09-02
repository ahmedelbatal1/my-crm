<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import DataTable from '@/Components/DataTable.vue';
import ConfirmAction from '@/Components/ConfirmAction.vue';

defineProps({
  companies: { type: Array, required: true },
});

/*
 * This screen receives no contacts_count, so a company that still has contacts cannot be
 * pre-empted client-side the way a project can (FR-038 applies only "where a screen
 * already carries the data"). Adding the count would mean editing CompanyController,
 * which FR-043 forbids. A blocked attempt therefore lands on the styled 403 page, whose
 * copy names dependent records as the usual cause (FR-037, FR-041).
 */
function destroy(company) {
  router.delete(`/companies/${company.id}`);
}

const columns = [
  { key: 'name', label: 'Company' },
];
</script>

<template>
  <AppLayout>
    <PageHeader title="Companies" description="Every company on the books, shared across the whole team.">
      <template #action>
        <AppButton href="/companies/create">New Company</AppButton>
      </template>
    </PageHeader>

    <DataTable
      :columns="columns"
      :rows="companies"
      :row-href="(company) => `/companies/${company.id}/edit`"
      empty-title="No companies yet"
      empty-description="Companies group contacts that buy on behalf of an organisation. Individual buyers do not need one."
    >
      <template #actions="{ row }">
        <ConfirmAction :question="`Delete ${row.name}?`" @confirmed="destroy(row)" />
      </template>

      <template #empty-action>
        <AppButton href="/companies/create">New Company</AppButton>
      </template>
    </DataTable>
  </AppLayout>
</template>
