<script setup>
import { useForm } from '@inertiajs/vue3';
import AppButton from '@/Components/AppButton.vue';
import FormField from '@/Components/FormField.vue';

/*
 * Outside the authenticated shell — guests reach this screen — but it uses the same
 * tokens, form controls and button treatment as everything else (FR-014).
 */
const form = useForm({
  email: '',
  password: '',
});

function submit() {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-surface px-4 py-12">
    <div class="w-full max-w-sm">
      <p class="mb-8 text-center text-page-title font-bold text-ink-strong">My CRM</p>

      <div class="rounded-panel border border-line bg-surface-raised p-8">
        <h1 class="mb-6 text-section font-semibold text-ink-strong">Sign in to your account</h1>

        <form class="space-y-5" @submit.prevent="submit">
          <FormField id="email" label="Email" required :error="form.errors.email">
            <input id="email" v-model="form.email" type="email" autocomplete="username" />
          </FormField>

          <FormField id="password" label="Password" required :error="form.errors.password">
            <input id="password" v-model="form.password" type="password" autocomplete="current-password" />
          </FormField>

          <AppButton type="submit" :loading="form.processing" class="w-full">Sign in</AppButton>
        </form>
      </div>
    </div>
  </div>
</template>
