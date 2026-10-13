<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Product details used as filters on the rmflooring.ca shop. Stored as JSON in
 * product_lines.shop_attributes; which fields apply depends on the product type.
 * Option keys are stable slugs (the website filters on them); labels can be reworded.
 */
class ShopAttributes
{
    public const FIELDS = [
        'construction' => ['label' => 'Construction', 'type' => 'select', 'for' => ['hardwood'],
            'options' => ['engineered' => 'Engineered', 'solid' => 'Solid']],
        'species' => ['label' => 'Species', 'type' => 'select', 'for' => ['hardwood'],
            'options' => ['white-oak' => 'White oak', 'red-oak' => 'Red oak', 'european-oak' => 'European oak', 'oak' => 'Oak', 'hickory' => 'Hickory',
                'maple' => 'Maple', 'walnut' => 'Walnut', 'birch' => 'Birch', 'acacia' => 'Acacia', 'ash' => 'Ash', 'elm' => 'Elm', 'cherry' => 'Cherry',
                'jatoba' => 'Jatoba', 'teak' => 'Teak', 'bamboo' => 'Bamboo', 'other' => 'Other']],
        'wear_layer' => ['label' => 'Wear layer', 'type' => 'text', 'for' => ['hardwood', 'vinyl'], 'placeholder' => 'e.g. 2mm or 20 mil', 'max' => 20],
        'ac_rating' => ['label' => 'AC rating', 'type' => 'select', 'for' => ['laminate'],
            'options' => ['ac2' => 'AC2', 'ac3' => 'AC3', 'ac4' => 'AC4', 'ac5' => 'AC5', 'ac6' => 'AC6']],
        'core' => ['label' => 'Core / type', 'type' => 'select', 'for' => ['vinyl'],
            'options' => ['spc' => 'SPC (rigid core)', 'wpc' => 'WPC', 'dryback' => 'Dryback (glue down)', 'loose-lay' => 'Loose lay']],
        'finish' => ['label' => 'Finish / texture', 'type' => 'select', 'for' => ['hardwood', 'laminate', 'vinyl'],
            'options' => ['matte' => 'Matte', 'satin' => 'Satin', 'semi-gloss' => 'Semi-gloss', 'gloss' => 'Gloss', 'oiled' => 'Oiled',
                'wire-brushed' => 'Wire-brushed', 'hand-scraped' => 'Hand-scraped', 'distressed' => 'Distressed', 'embossed' => 'Embossed (EIR)', 'smooth' => 'Smooth']],
        'edge' => ['label' => 'Edge', 'type' => 'select', 'for' => ['hardwood', 'laminate', 'vinyl'],
            'options' => ['square' => 'Square', 'micro-bevel' => 'Micro-bevel', 'bevelled' => 'Bevelled', 'painted-bevel' => 'Painted bevel']],
        'installation' => ['label' => 'Installation', 'type' => 'multi', 'for' => ['hardwood', 'laminate', 'vinyl'],
            'options' => ['click' => 'Click / floating', 'glue' => 'Glue down', 'nail' => 'Nail / staple', 'loose-lay' => 'Loose lay']],
        'water' => ['label' => 'Water resistance', 'type' => 'select', 'for' => ['hardwood', 'laminate', 'vinyl'],
            'options' => ['waterproof' => 'Waterproof', 'water-resistant' => 'Water resistant', 'none' => 'Not water resistant']],
        'pad_attached' => ['label' => 'Pad attached', 'type' => 'select', 'for' => ['laminate', 'vinyl'],
            'options' => ['yes' => 'Yes', 'no' => 'No']],
        'fibre' => ['label' => 'Fibre', 'type' => 'select', 'for' => ['carpet'],
            'options' => ['nylon' => 'Nylon', 'polyester' => 'Polyester / PET', 'triexta' => 'Triexta', 'wool' => 'Wool', 'polypropylene' => 'Polypropylene (olefin)', 'blend' => 'Blend']],
        'pile' => ['label' => 'Style', 'type' => 'select', 'for' => ['carpet'],
            'options' => ['cut' => 'Cut pile (plush / saxony)', 'loop' => 'Loop (berber)', 'cut-loop' => 'Cut & loop (pattern)', 'frieze' => 'Frieze / textured']],
        'material' => ['label' => 'Material', 'type' => 'select', 'for' => ['tile'],
            'options' => ['porcelain' => 'Porcelain', 'ceramic' => 'Ceramic', 'stone' => 'Natural stone', 'glass' => 'Glass']],
    ];

    /** Per colour (product style). */
    public const TONES = ['light' => 'Light / white', 'grey' => 'Grey', 'beige' => 'Beige / natural', 'brown' => 'Brown', 'dark' => 'Dark / black', 'multi' => 'Multi / pattern'];

    /** Product type name → attribute family (null = no extra details, e.g. Adhesives). */
    public static function family(?string $typeName): ?string
    {
        $name = Str::lower((string) $typeName);

        return match (true) {
            str_contains($name, 'hardwood') => 'hardwood',
            str_contains($name, 'laminate') => 'laminate',
            str_contains($name, 'lvp'), str_contains($name, 'lvt'), $name === 'vinyl', str_contains($name, 'vct') => 'vinyl',
            str_contains($name, 'carpet') => 'carpet',
            str_contains($name, 'tile') => 'tile',
            default => null,
        };
    }

    /** Fields that apply to a product type. */
    public static function fieldsFor(?string $typeName): array
    {
        $family = self::family($typeName);

        return $family ? array_filter(self::FIELDS, fn ($f) => in_array($family, $f['for'], true)) : [];
    }

    /** Validation rules for the `attrs` form input. */
    public static function rules(): array
    {
        $rules = ['attrs' => 'nullable|array'];
        foreach (self::FIELDS as $key => $field) {
            $rules["attrs.{$key}"] = match ($field['type']) {
                'text' => 'nullable|string|max:' . ($field['max'] ?? 50),
                'multi' => 'nullable|array',
                default => 'nullable|in:' . implode(',', array_keys($field['options'])),
            };
            if ($field['type'] === 'multi') {
                $rules["attrs.{$key}.*"] = 'in:' . implode(',', array_keys($field['options']));
            }
        }

        return $rules;
    }

    /** Keeps only filled fields that apply to this product type; null when nothing is set. */
    public static function clean(?array $input, ?string $typeName): ?array
    {
        $clean = [];
        foreach (self::fieldsFor($typeName) as $key => $field) {
            $value = $input[$key] ?? null;
            if ($field['type'] === 'multi') {
                $value = array_values(array_intersect(array_keys($field['options']), (array) $value));
            } elseif (is_string($value)) {
                $value = trim($value);
            }
            if ($value !== null && $value !== '' && $value !== []) {
                $clean[$key] = $value;
            }
        }

        return $clean ?: null;
    }

    /** Human-readable values, e.g. for the product page spec list. */
    public static function display(?array $attributes, ?string $typeName): array
    {
        $out = [];
        foreach (self::fieldsFor($typeName) as $key => $field) {
            $value = $attributes[$key] ?? null;
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            $out[$field['label']] = match ($field['type']) {
                'text' => $value,
                'multi' => implode(', ', array_map(fn ($v) => $field['options'][$v] ?? $v, (array) $value)),
                default => $field['options'][$value] ?? $value,
            };
        }

        return $out;
    }
}
