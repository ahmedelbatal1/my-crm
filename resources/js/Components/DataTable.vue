<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/EmptyState.vue';
import { formatters, ABSENT } from '@/lib/format.js';

/*
 * The one table treatment for every record list (FR-020 – FR-027). Column definitions
 * carry alignment and formatting so those decisions are structural rather than
 * re-made on each page — which is what keeps eight list surfaces consistent.
 *
 * Column shape: { key, label, align: 'left'|'right', format: 'money'|'area'|'number'|'date',
 *                 truncate: bool }
 */
const props = defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, required: true },
  rowHref: { type: Function, default: null },
  emptyTitle: { type: String, default: 'Nothing here yet' },
  emptyDescription: { type: String, default: null },
});

const isEmpty = computed(() => props.rows.length === 0);

/** Walks a dotted path so a column can read 'unit.project.name'. */
function valueAt(row, key) {
  return key.split('.').reduce((carry, part) => (carry == null ? null : carry[part]), row);
}

/** FR-024/FR-025: absent renders as an em dash, a real 0 renders as 0.00. */
function display(row, column) {
  const raw = valueAt(row, column.key);

  if (column.format && formatters[column.format]) {
    return formatters[column.format](raw);
  }

  return raw === null || raw === undefined || raw === '' ? ABSENT : raw;
}

function cellClasses(column) {
  return [
    // FR-021: numbers right, text left. FR-006: equal-width digits so columns
    // align on the decimal separator.
    column.align === 'right' ? 'text-right tabular' : 'text-left',
    column.truncate !== false && column.align !== 'right' ? 'max-w-[18rem] truncate' : '',
  ];
}
</script>

<template>
  <EmptyState v-if="isEmpty" :title="emptyTitle" :description="emptyDescription">
    <template #action>
      <slot name="empty-action" />
    </template>
  </EmptyState>

  <!-- Wide tables scroll inside their own container; the page never scrolls sideways. -->
  <div v-else class="overflow-x-auto rounded-panel border border-line bg-surface-raised">
    <table class="w-full border-collapse text-body">
      <thead>
        <tr class="bg-surface-sunken">
          <th
            v-for="column in columns"
            :key="column.key"
            scope="col"
            class="px-4 py-3 text-support font-semibold uppercase tracking-wide text-ink-muted"
            :class="column.align === 'right' ? 'text-right' : 'text-left'"
          >
            {{ column.label }}
          </th>
          <th v-if="$slots.actions" scope="col" class="px-4 py-3">
            <span class="sr-only">Actions</span>
          </th>
        </tr>
      </thead>

      <tbody>
        <tr
          v-for="(row, index) in rows"
          :key="row.id ?? index"
          class="border-t border-line transition hover:bg-surface-sunken"
        >
          <td
            v-for="column in columns"
            :key="column.key"
            class="px-4 py-3 align-middle text-ink"
            :class="cellClasses(column)"
          >
            <!-- FR-025: the whole row is one navigable target, the same way on every list. -->
            <Link
              v-if="rowHref && column === columns[0]"
              :href="rowHref(row)"
              class="font-medium text-ink hover:text-primary-text"
            >
              <slot :name="`cell:${column.key}`" :row="row">{{ display(row, column) }}</slot>
            </Link>
            <slot v-else :name="`cell:${column.key}`" :row="row">{{ display(row, column) }}</slot>
          </td>

          <td v-if="$slots.actions" class="px-4 py-3 text-right">
            <slot name="actions" :row="row" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
