<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Architecture-fitness tests for the design system (research.md #5).
 *
 * These read resources/js as text. That is deliberate: "every screen uses the shared
 * shell" and "no page invents its own colour" are the requirements most likely to erode
 * silently, and they are checkable without a JS test runner (Principle V: no new
 * dependency). Assertions are grouped by the user story that makes each one true.
 */
class DesignSystemFitnessTest extends TestCase
{
    /** Screens that sit outside the authenticated shell by design. */
    private const OUTSIDE_THE_SHELL = [
        'Auth/Login.vue',   // guests only (FR-014)
        'Home.vue',         // public landing (research.md #12)
        'Errors/Error.vue', // may render for guests
    ];

    /*
     * ---------------------------------------------------------------- US1: the shell
     */

    /** SC-001: every authenticated screen renders inside the one shared shell. */
    public function test_every_authenticated_page_uses_the_shared_shell(): void
    {
        foreach ($this->pages() as $relative => $source) {
            if (in_array($relative, self::OUTSIDE_THE_SHELL, true)) {
                $this->assertStringNotContainsString(
                    'AppLayout',
                    $source,
                    "{$relative} is documented as sitting outside the shell but imports AppLayout"
                );

                continue;
            }

            $this->assertStringContainsString(
                "import AppLayout from '@/Layouts/AppLayout.vue'",
                $source,
                "{$relative} does not render inside the shared shell (SC-001)"
            );
        }
    }

    /**
     * SC-004 / FR-001: colour comes from the token layer only. This is the assertion
     * that stops the app drifting back to per-page styling, which is the whole reason
     * this feature exists.
     */
    public function test_no_file_hardcodes_a_colour_or_uses_a_stock_tailwind_palette(): void
    {
        $stockPalettes = [
            'slate', 'gray', 'grey', 'zinc', 'neutral', 'stone',
            'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal',
            'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose',
        ];

        foreach ($this->sources() as $relative => $source) {
            $this->assertDoesNotMatchRegularExpression(
                '/#[0-9a-fA-F]{3,8}\b/',
                $source,
                "{$relative} contains a literal hex colour; use a semantic token instead (SC-004)"
            );

            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:rgb|rgba|hsl|hsla)\(/',
                $source,
                "{$relative} contains a literal colour function; use a semantic token instead (SC-004)"
            );

            foreach ($stockPalettes as $palette) {
                $this->assertDoesNotMatchRegularExpression(
                    '/\b(?:bg|text|border|ring|outline|from|to|via|divide|decoration|shadow|accent|caret|fill|stroke|placeholder)-'
                        .$palette.'-\d{2,3}\b/',
                    $source,
                    "{$relative} uses the stock Tailwind '{$palette}' palette; use a semantic token instead (FR-001)"
                );
            }
        }
    }

    /*
     * -------------------------------------------------- US2: the status vocabulary
     */

    /** FR-015: one indicator covers all seven values across both kinds. */
    public function test_the_status_badge_covers_all_seven_values_and_labels(): void
    {
        $badge = file_get_contents($this->root().'/resources/js/Components/StatusBadge.vue');

        $this->assertNotFalse($badge, 'StatusBadge.vue is missing');

        $values = ['lead', 'reserved', 'contracted_won', 'lost', 'available', 'sold'];
        foreach ($values as $value) {
            $this->assertStringContainsString(
                $value,
                $badge,
                "StatusBadge does not handle the '{$value}' value (FR-015)"
            );
        }

        $labels = ['Lead', 'Reserved', 'Contracted / Won', 'Lost', 'Available', 'Sold'];
        foreach ($labels as $label) {
            $this->assertStringContainsString(
                $label,
                $badge,
                "StatusBadge does not render the '{$label}' label (FR-015)"
            );
        }

        // FR-017: the two kinds must be distinguishable, which requires knowing which is which.
        $this->assertStringContainsString('availability', $badge, 'StatusBadge has no kind distinction (FR-017)');
        $this->assertStringContainsString('stage', $badge, 'StatusBadge has no kind distinction (FR-017)');
    }

    /**
     * FR-016 / SC-002: a raw enum value must never reach the screen. Only the <template>
     * block is examined — the label map in <script> necessarily names the raw values.
     */
    public function test_no_raw_enum_value_reaches_a_template(): void
    {
        foreach ($this->sources() as $relative => $source) {
            if (! str_ends_with($relative, '.vue')) {
                continue;
            }

            $template = $this->templateBlock($source);

            // Without this the assertion below could pass vacuously on a parse miss.
            $this->assertNotSame('', $template, "Could not extract a <template> block from {$relative}");

            foreach (['contracted_won', 'sales_rep'] as $rawValue) {
                $this->assertStringNotContainsString(
                    $rawValue,
                    $template,
                    "{$relative} renders the raw value '{$rawValue}'; use its human label (FR-016)"
                );
            }
        }
    }

    /** SC-002: the old duplicate badge is gone and nothing still reaches for it. */
    public function test_nothing_imports_the_retired_stage_badge(): void
    {
        $this->assertFileDoesNotExist(
            $this->root().'/resources/js/Components/StageBadge.vue',
            'StageBadge.vue was folded into StatusBadge and must be deleted'
        );

        // Checks for live references — an import or a tag — not any mention of the name,
        // so a comment recording why it was retired stays allowed.
        foreach ($this->sources() as $relative => $source) {
            $this->assertStringNotContainsString(
                "Components/StageBadge.vue'",
                $source,
                "{$relative} still imports the retired StageBadge component"
            );

            $this->assertStringNotContainsString(
                '<StageBadge',
                $source,
                "{$relative} still renders the retired StageBadge component"
            );
        }
    }

    /*
     * ------------------------------------------- US3: records, tables and the board
     */

    /** FR-020: one table treatment. No page may hand-roll its own table markup. */
    public function test_every_list_page_uses_the_shared_table(): void
    {
        $listPages = [
            'Contacts/Index.vue',
            'Companies/Index.vue',
            'Projects/Index.vue',
            'Projects/Show.vue',
            'Contacts/Show.vue',
        ];

        $pages = $this->pages();

        foreach ($listPages as $page) {
            $this->assertArrayHasKey($page, $pages, "{$page} is missing");
            $this->assertStringContainsString(
                "import DataTable from '@/Components/DataTable.vue'",
                $pages[$page],
                "{$page} does not use the shared table treatment (FR-020)"
            );
        }

        foreach ($pages as $relative => $source) {
            $this->assertStringNotContainsString(
                '<table',
                $source,
                "{$relative} hand-rolls its own table markup; use DataTable (FR-020)"
            );
        }
    }

    /** FR-031: detail screens share one field/value pattern. */
    public function test_every_detail_page_uses_the_shared_description_list(): void
    {
        $pages = $this->pages();

        foreach (['Contacts/Show.vue', 'Deals/Show.vue'] as $page) {
            $this->assertStringContainsString(
                "import DescriptionList from '@/Components/DescriptionList.vue'",
                $pages[$page],
                "{$page} does not use the shared field/value pattern (FR-031)"
            );
        }
    }

    /**
     * FR-029 — the hardest contract in the feature. The pipeline board is a READING
     * surface: opening a Deal is the only route to changing its stage. Without this
     * assertion the invariant would rest on a human remembering to look.
     */
    public function test_the_pipeline_board_has_no_write_path(): void
    {
        $boardFiles = [
            'resources/js/Components/PipelineBoard.vue',
            'resources/js/Components/DealCard.vue',
            'resources/js/Pages/Deals/Index.vue',
        ];

        $writeSignals = [
            'useForm', 'router.put', 'router.post', 'router.patch', 'router.delete',
            'draggable', '@drop', '@dragstart', 'v-model',
        ];

        foreach ($boardFiles as $file) {
            $path = $this->root().'/'.$file;
            $this->assertFileExists($path, "{$file} is missing");

            $source = file_get_contents($path);

            foreach ($writeSignals as $signal) {
                $this->assertStringNotContainsString(
                    $signal,
                    $source,
                    "{$file} contains '{$signal}' — the pipeline board must not write a stage (FR-029)"
                );
            }
        }
    }

    /*
     * ------------------------------------------------------- US4: the state patterns
     */

    /** FR-032/FR-034/FR-035: every list has an empty state, every form a field pattern. */
    public function test_every_list_and_form_uses_the_shared_state_patterns(): void
    {
        $pages = $this->pages();

        foreach ($pages as $relative => $source) {
            if (! str_contains($relative, 'Form.vue') || str_contains($relative, 'Auth/')) {
                continue;
            }

            $this->assertStringContainsString(
                "import FormField from '@/Components/FormField.vue'",
                $source,
                "{$relative} does not use the shared field pattern (FR-034, FR-036)"
            );

            $this->assertStringContainsString(
                'form.processing',
                $source,
                "{$relative} does not guard against a double submission (FR-035)"
            );
        }

        // Lists get their empty state through DataTable, or declare one directly.
        foreach (['Contacts/Index.vue', 'Companies/Index.vue', 'Projects/Index.vue'] as $page) {
            $this->assertMatchesRegularExpression(
                '/DataTable|EmptyState/',
                $pages[$page],
                "{$page} has no empty state (FR-032)"
            );
        }
    }

    /** FR-040: destructive actions are confirmed, and FR-038 pre-empts blocked ones. */
    public function test_every_delete_control_goes_through_the_confirm_pattern(): void
    {
        $pages = $this->pages();

        foreach ($pages as $relative => $source) {
            if (! str_contains($source, 'router.delete') && ! str_contains($source, 'method="delete"')) {
                continue;
            }

            $this->assertStringContainsString(
                "import ConfirmAction from '@/Components/ConfirmAction.vue'",
                $source,
                "{$relative} deletes a record without the shared confirm pattern (FR-040)"
            );
        }
    }

    /**
     * FR-042: the Deal form's bespoke "already sold" banner from feature 002 must be
     * replaced by the shared status indicator and blocked-action treatment, leaving no
     * hand-styled remnant.
     */
    public function test_the_deal_form_uses_shared_patterns_for_the_sold_unit_state(): void
    {
        $form = $this->pages()['Deals/Form.vue'];

        $this->assertStringContainsString(
            "import StatusBadge from '@/Components/StatusBadge.vue'",
            $form,
            'The Deal form must show unit availability through the shared indicator (FR-042)'
        );

        $this->assertStringNotContainsString(
            'Unit already sold.',
            $form,
            'The bespoke sold-unit banner text from feature 002 is still present (FR-042)'
        );
    }

    /* ------------------------------------------------------------------ helpers */

    /** The <template> section of a single-file component, or '' when absent. */
    private function templateBlock(string $source): string
    {
        preg_match('/<template>(.*)<\/template>/s', $source, $match);

        return $match[1] ?? '';
    }

    /** Every Inertia page, keyed by its path relative to Pages/. */
    private function pages(): array
    {
        $root = $this->root().'/resources/js/Pages';

        $pages = [];
        foreach ($this->vueFiles($root) as $path) {
            $pages[$this->relative($path, $root)] = file_get_contents($path);
        }

        $this->assertNotEmpty($pages, 'No Inertia pages found under resources/js/Pages');

        return $pages;
    }

    /** Every source file under resources/js, keyed by its path relative to js/. */
    private function sources(): array
    {
        $root = $this->root().'/resources/js';

        $sources = [];
        foreach ($this->vueFiles($root, ['vue', 'js']) as $path) {
            $sources[$this->relative($path, $root)] = file_get_contents($path);
        }

        $this->assertNotEmpty($sources, 'No source files found under resources/js');

        return $sources;
    }

    /** Normalises a Windows path into the forward-slash form the assertions name. */
    private function relative(string $path, string $root): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($root) + 1));
    }

    private function vueFiles(string $directory, array $extensions = ['vue']): array
    {
        $found = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), $extensions, true)) {
                $found[] = $file->getPathname();
            }
        }

        sort($found);

        return $found;
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
