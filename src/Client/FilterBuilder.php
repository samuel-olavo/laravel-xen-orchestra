<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Client;

/**
 * Assembles Xen Orchestra "complex-matcher" filter expressions.
 *
 * A deliberate design note, because it is the one place where a friendlier API
 * would have been a trap: XO's `filter` is a single expression string, not a
 * set of key/value pairs. It supports negation (`!power_state:Halted`), nested
 * property paths (`body:name:physical_utilisation`), regular expressions
 * (`name_label:/^prod/`) and alternation (`|(a b)`).
 *
 * A `filter($field, $value)` signature can express exactly one of those — plain
 * equality on a top-level field — and there is no way to grow it afterwards
 * without bolting a second `filterRaw()` onto the public API.
 *
 * So the raw expression is the primary interface, and this builder is optional
 * sugar that produces the same strings. Anything the builder cannot express,
 * you write by hand and lose nothing.
 */
final class FilterBuilder
{
    /** @param list<string> $terms */
    private function __construct(private array $terms = [])
    {
    }

    public static function make(): self
    {
        return new self();
    }

    /**
     * `field:value`. Nested paths are expressed as an array of segments,
     * matching XO's colon-separated navigation.
     *
     * @param  string|list<string>  $field
     */
    public function where(string|array $field, mixed $value = null): self
    {
        $path = is_array($field) ? implode(':', $field) : $field;

        if ($value === null) {
            return $this->add($path);
        }

        return $this->add($path.':'.self::quote($value));
    }

    /** `!field:value` */
    public function whereNot(string|array $field, mixed $value = null): self
    {
        $path = is_array($field) ? implode(':', $field) : $field;

        return $this->add('!'.($value === null ? $path : $path.':'.self::quote($value)));
    }

    /**
     * `field:/pattern/` — XO applies the regular expression to the property.
     * The pattern is passed through untouched; slashes are added if absent.
     */
    public function whereMatches(string $field, string $pattern): self
    {
        if (! str_starts_with($pattern, '/')) {
            $pattern = '/'.$pattern.'/';
        }

        return $this->add($field.':'.$pattern);
    }

    /**
     * `|(field:a field:b)` — matches when any of the values matches.
     *
     * @param  list<mixed>  $values
     */
    public function whereIn(string $field, array $values): self
    {
        if ($values === []) {
            return $this;
        }

        $alternatives = array_map(
            fn ($value) => $field.':'.self::quote($value),
            $values,
        );

        return $this->add('|('.implode(' ', $alternatives).')');
    }

    /** Property exists and is truthy. */
    public function whereHas(string $field): self
    {
        return $this->add($field.'?');
    }

    /** Drop in an expression you wrote yourself, ANDed with the rest. */
    public function raw(string $expression): self
    {
        return $this->add(trim($expression));
    }

    private function add(string $term): self
    {
        $clone = clone $this;
        $clone->terms[] = $term;

        return $clone;
    }

    /**
     * Values containing whitespace or reserved characters must be quoted, or XO
     * reads the space as a term separator.
     */
    private static function quote(mixed $value): string
    {
        $value = match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => (string) $value,
        };

        if ($value !== '' && preg_match('/[\s"()|!:]/', $value) !== 1) {
            return $value;
        }

        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }

    /** Terms are space-separated, which is AND in complex-matcher. */
    public function toString(): string
    {
        return implode(' ', $this->terms);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function isEmpty(): bool
    {
        return $this->terms === [];
    }
}
