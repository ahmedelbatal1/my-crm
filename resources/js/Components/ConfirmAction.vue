<script setup>
import { ref } from 'vue';
import AppButton from '@/Components/AppButton.vue';

/*
 * Inline two-step confirmation for destructive actions (FR-040).
 *
 * Deliberately not a modal (research.md #10): a real dialog needs a focus trap, scroll
 * lock, aria-modal and a return-focus contract — roughly a hundred lines of
 * accessibility-critical code, or a new dependency, for a handful of delete buttons.
 * Arming in place is keyboard-operable with none of that machinery.
 *
 * The `disabled` + `reason` mode is FR-038: where the screen already holds the data
 * proving an action is blocked, the control says so up front instead of letting the
 * user attempt it and fail.
 */
const props = defineProps({
  label: { type: String, default: 'Delete' },
  confirmLabel: { type: String, default: 'Confirm' },
  question: { type: String, default: 'Delete this?' },
  disabled: { type: Boolean, default: false },
  reason: { type: String, default: null },
});

const emit = defineEmits(['confirmed']);

const armed = ref(false);

function arm() {
  if (!props.disabled) {
    armed.value = true;
  }
}

function cancel() {
  armed.value = false;
}

function confirm() {
  armed.value = false;
  emit('confirmed');
}
</script>

<template>
  <!-- FR-038: blocked before it is attempted, with the reason stated. -->
  <span v-if="disabled" class="inline-flex items-center gap-2">
    <span class="text-body font-medium text-ink-disabled">{{ label }}</span>
    <span v-if="reason" class="text-support text-ink-muted">{{ reason }}</span>
  </span>

  <span v-else-if="armed" class="inline-flex flex-wrap items-center justify-end gap-2" @keydown.esc="cancel">
    <span class="text-body text-ink-muted">{{ question }}</span>
    <AppButton variant="danger" @click="confirm">{{ confirmLabel }}</AppButton>
    <AppButton variant="ghost" @click="cancel">Cancel</AppButton>
  </span>

  <AppButton v-else variant="ghost" @click="arm">{{ label }}</AppButton>
</template>
