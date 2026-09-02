<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import DataTable from '@/Components/DataTable.vue';

defineProps({
  contacts: { type: Array, required: true },
});

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'phone', label: 'Phone' },
  { key: 'company.name', label: 'Company' },
];
</script>

<template>
  <AppLayout>
    <PageHeader title="Contacts">
      <template #action>
        <AppButton href="/contacts/create">New Contact</AppButton>
      </template>
    </PageHeader>

    <!--
      FR-033: this list is scoped to the acting rep, so the empty copy says the list is
      empty *for you* rather than implying the whole system has no contacts.
    -->
    <DataTable
      :columns="columns"
      :rows="contacts"
      :row-href="(contact) => `/contacts/${contact.id}`"
      empty-title="None of your contacts yet"
      empty-description="Contacts you own appear here. Another rep's contacts are never listed on this screen."
    >
      <template #cell:company.name="{ row }">
        <span :class="row.company ? 'text-ink' : 'text-ink-muted'">
          {{ row.company?.name ?? 'Individual buyer' }}
        </span>
      </template>

      <template #empty-action>
        <AppButton href="/contacts/create">New Contact</AppButton>
      </template>
    </DataTable>
  </AppLayout>
</template>
