<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppButton from '@/Components/AppButton.vue';
import PipelineBoard from '@/Components/PipelineBoard.vue';

defineProps({
  dealsByStage: { type: Object, required: true },
});
</script>

<template>
  <AppLayout>
    <PageHeader title="Pipeline" description="Your deals, grouped by the stage they have reached.">
      <template #action>
        <AppButton href="/deals/create">New Deal</AppButton>
      </template>
    </PageHeader>

    <!-- Read-only by contract: opening a deal is the only way to change its stage (FR-029). -->
    <PipelineBoard :deals-by-stage="dealsByStage">
      <template #empty-action>
        <AppButton href="/deals/create">New Deal</AppButton>
      </template>
    </PipelineBoard>
  </AppLayout>
</template>
