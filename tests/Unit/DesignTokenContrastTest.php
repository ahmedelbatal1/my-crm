<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * FR-004 / SC-003: every colour pairing the design system uses must meet WCAG 2.1 AA
 * contrast. The pairings are declared in specs/003-design-system-shell/data-model.md §4;
 * this test recomputes them from the real token block so a later palette tweak cannot
 * quietly drop below the line.
 *
 * `--color-line` is deliberately absent: WCAG 1.4.11 exempts purely decorative
 * boundaries, and holding a row divider to 3:1 would cage every table (research.md #3).
 */
class DesignTokenContrastTest extends TestCase
{
    /** Text needs 4.5:1; non-text (borders, focus rings) needs 3:1. */
    private const PAIRINGS = [
        ['--color-ink', '--color-surface', 4.5],
        ['--color-ink-strong', '--color-surface', 4.5],
        ['--color-ink-muted', '--color-surface', 4.5],
        ['--color-ink-muted', '--color-surface-sunken', 4.5],
        ['#FFFFFF', '--color-primary', 4.5],
        ['#FFFFFF', '--color-primary-hover', 4.5],
        ['--color-primary-text', '--color-surface', 4.5],
        ['--color-focus', '--color-surface', 3.0],
        ['--color-line-strong', '--color-surface', 3.0],
        ['--color-line-strong', '--color-surface-raised', 3.0],
        ['--color-quiet-800', '--color-quiet-100', 4.5],
        ['--color-ochre-800', '--color-ochre-100', 4.5],
        ['--color-palm-800', '--color-palm-100', 4.5],
        ['--color-brick-800', '--color-brick-100', 4.5],
        ['--color-ochre-900', '--color-ochre-100', 4.5],
        ['--color-palm-900', '--color-palm-100', 4.5],
        ['--color-brick-900', '--color-brick-100', 4.5],
        ['#FFFFFF', '--color-ink-800', 4.5],
    ];

    public function test_every_declared_pairing_meets_its_wcag_threshold(): void
    {
        $tokens = $this->tokens();

        foreach (self::PAIRINGS as [$foreground, $background, $threshold]) {
            $fg = $this->resolve($foreground, $tokens);
            $bg = $this->resolve($background, $tokens);

            $ratio = $this->contrast($fg, $bg);

            $this->assertGreaterThanOrEqual(
                $threshold,
                round($ratio, 2),
                sprintf(
                    '%s (%s) on %s (%s) measures %.2f:1 but needs %.1f:1',
                    $foreground, $fg, $background, $bg, $ratio, $threshold
                )
            );
        }
    }

    public function test_the_semantic_tier_and_status_families_are_all_defined(): void
    {
        $tokens = $this->tokens();

        $required = [
            '--color-surface', '--color-surface-raised', '--color-surface-sunken',
            '--color-ink', '--color-ink-strong', '--color-ink-muted', '--color-ink-disabled',
            '--color-line', '--color-line-strong',
            '--color-primary', '--color-primary-hover', '--color-primary-text', '--color-focus',
            '--color-quiet-100', '--color-quiet-800',
            '--color-ochre-100', '--color-ochre-800', '--color-ochre-900',
            '--color-palm-100', '--color-palm-800', '--color-palm-900',
            '--color-brick-100', '--color-brick-800', '--color-brick-900',
        ];

        foreach ($required as $token) {
            $this->assertArrayHasKey($token, $tokens, "{$token} is not defined in the @theme block");
        }
    }

    /**
     * FR-002 / Principle V: the four status families are the single semantic set.
     * No parallel success/warning/danger/info palette may exist.
     */
    public function test_no_parallel_semantic_colour_set_exists(): void
    {
        $tokens = array_keys($this->tokens());

        foreach (['success', 'warning', 'danger', 'info', 'moss', 'teal'] as $forbidden) {
            $matches = array_filter($tokens, fn ($token) => str_contains($token, $forbidden));

            $this->assertSame(
                [],
                array_values($matches),
                "FR-002 forbids a parallel '{$forbidden}' colour vocabulary alongside the four status families"
            );
        }
    }

    /** @return array<string, string> */
    private function tokens(): array
    {
        $path = dirname(__DIR__, 2).'/resources/css/app.css';
        $css = file_get_contents($path);

        $this->assertNotFalse($css, "Could not read {$path}");

        preg_match('/@theme\s*\{(.*?)\n\}/s', $css, $block);
        $this->assertNotEmpty($block, 'No @theme block found in resources/css/app.css');

        preg_match_all('/(--[\w-]+)\s*:\s*([^;]+);/', $block[1], $pairs, PREG_SET_ORDER);

        $tokens = [];
        foreach ($pairs as $pair) {
            $tokens[$pair[1]] = trim($pair[2]);
        }

        return $tokens;
    }

    /** Resolves a literal hex, or a token that may point at another token via var(). */
    private function resolve(string $reference, array $tokens, int $depth = 0): string
    {
        $this->assertLessThan(10, $depth, "Token reference loop while resolving {$reference}");

        if (str_starts_with($reference, '#')) {
            return strtoupper($reference);
        }

        $this->assertArrayHasKey($reference, $tokens, "{$reference} is not defined in the @theme block");

        $value = $tokens[$reference];

        if (preg_match('/var\(\s*(--[\w-]+)\s*\)/', $value, $inner)) {
            return $this->resolve($inner[1], $tokens, $depth + 1);
        }

        return strtoupper($value);
    }

    private function contrast(string $a, string $b): float
    {
        $la = $this->relativeLuminance($a);
        $lb = $this->relativeLuminance($b);

        $lighter = max($la, $lb);
        $darker = min($la, $lb);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private function relativeLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        $this->assertSame(6, strlen($hex), "Expected a 6-digit hex colour, got '{$hex}'");

        $channels = [];
        foreach ([0, 2, 4] as $offset) {
            $value = hexdec(substr($hex, $offset, 2)) / 255;

            $channels[] = $value <= 0.04045
                ? $value / 12.92
                : (($value + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
