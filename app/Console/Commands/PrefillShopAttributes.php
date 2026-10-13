<?php

namespace App\Console\Commands;

use App\Models\ProductLine;
use App\Services\ShopCacheService;
use App\Support\ShopAttributes;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * One-off helper: guesses shop filter details (species, AC rating, core, finish, installation,
 * colour tone, ...) from product names, descriptions and colour codes, so staff only have to
 * check and fill gaps. Only fills EMPTY fields — never overwrites what staff entered.
 * Dry run by default; --apply saves.
 */
class PrefillShopAttributes extends Command
{
    protected $signature = 'shop:prefill-attributes {--apply : Save the guesses (otherwise just shows them)}';

    protected $description = 'Pre-fill shop filter details on product lines/styles from their names and descriptions (empty fields only)';

    /** Text pattern => option key, per field. First match wins for single-choice fields. */
    private const PATTERNS = [
        'construction' => ['/\bengineered|enginee?red\b/' => 'engineered', '/\bsolid\b/' => 'solid'],
        'species' => ['/\bwhite oak\b/' => 'white-oak', '/\bred oak\b/' => 'red-oak', '/\beuropean oak\b/' => 'european-oak', '/\bhickory\b/' => 'hickory',
            '/\bmaple\b/' => 'maple', '/\bwalnut\b/' => 'walnut', '/\bbirch\b/' => 'birch', '/\bacacia\b/' => 'acacia', '/\belm\b/' => 'elm',
            '/\bcherry\b/' => 'cherry', '/\bjatoba\b/' => 'jatoba', '/\bteak\b/' => 'teak', '/\bbamboo\b/' => 'bamboo', '/\boak\b/' => 'oak'],
        'ac_rating' => ['/\bac\s?([2-6])\b/' => 'ac$1'],
        'core' => ['/\bspc\b|rigid core/' => 'spc', '/\bwpc\b/' => 'wpc', '/dry\s?back|glue[\s-]down/' => 'dryback', '/loose[\s-]?lay/' => 'loose-lay'],
        'finish' => ['/wire[\s-]?brush|\bbrushed\b/' => 'wire-brushed', '/hand[\s-]?scraped/' => 'hand-scraped', '/distressed/' => 'distressed',
            '/\beir\b|embossed/' => 'embossed', '/\boiled\b|oil finish/' => 'oiled', '/\bmatte?\b/' => 'matte', '/semi[\s-]?gloss/' => 'semi-gloss', '/\bsatin\b/' => 'satin'],
        'edge' => ['/painted bevel/' => 'painted-bevel', '/micro[\s-]?bevel/' => 'micro-bevel', '/\bbevel/' => 'bevelled', '/square edge/' => 'square'],
        'water' => ['/waterproof|water[\s-]proof|100% water/' => 'waterproof', '/water[\s-]?resistant|aqua/' => 'water-resistant'],
        'pad_attached' => ['/pad attached|attached pad|attached (underlay|underpad|backing)|pre[\s-]?attached|ixpe|cork back|\+\s?1(\.\d)?\s?mm/' => 'yes'],
        'fibre' => ['/\bnylon\b/' => 'nylon', '/triexta|smartstrand/' => 'triexta', '/polyester|\bpet\b/' => 'polyester', '/\bwool\b/' => 'wool', '/polypropylene|olefin/' => 'polypropylene'],
        'pile' => ['/berber|\bloop\b/' => 'loop', '/frieze|textured/' => 'frieze', '/cut (&|and) loop/' => 'cut-loop', '/plush|saxony|cut pile/' => 'cut'],
        'material' => ['/porcelain/' => 'porcelain', '/ceramic/' => 'ceramic', '/marble|granite|travertine|slate|limestone|stone/' => 'stone', '/\bglass\b/' => 'glass'],
    ];

    private const INSTALL = ['/click|float/' => 'click', '/\bglue/' => 'glue', '/\bnail|staple/' => 'nail', '/loose[\s-]?lay/' => 'loose-lay'];

    private const TONES = [
        'dark' => '/\b(blk|black|dark|espresso|ebony|charcoal|onyx|midnight)\b/',
        'grey' => '/\b(gry|grey|gray|silver|smoke|slate|graphite)\b/',
        'brown' => '/\b(brn|brown|chestnut|caramel|mocha|cocoa|toast|butternut|walnut|coffee|cognac)\b/',
        'beige' => '/\b(bge|beige|tpe|taupe|cream|sand|natural|nat|wheat|linen)\b/',
        'light' => '/\b(wht|white|light|blonde?|ivory|snow|frost|pale|bianco)\b/',
    ];

    public function handle(): int
    {
        $apply = $this->option('apply');
        $lines = ProductLine::with(['productType', 'productStyles'])->where('status', 'active')->get();
        $lineChanges = 0;
        $toneChanges = 0;
        $rows = [];

        foreach ($lines as $line) {
            $fields = ShopAttributes::fieldsFor($line->productType?->name);
            $name = Str::lower(Str::ascii(implode(' ', [$line->name, $line->collection, $line->model])));
            $text = $name . ' ' . Str::lower(Str::ascii(implode(' ', [strip_tags((string) $line->shop_description), $line->productStyles->pluck('description')->implode(' ')])));

            $current = $line->shop_attributes ?? [];
            $guessed = [];
            foreach ($fields as $key => $field) {
                if (! empty($current[$key])) {
                    continue;   // staff value wins
                }
                // The product name beats the description (e.g. "water resistant" in the name, "waterproof" in marketing copy).
                $value = $key === 'installation' ? $this->installation($text) : ($this->first($key, $name) ?? $this->first($key, $text));
                if ($key === 'wear_layer') {
                    $value = preg_match('/(\d+(?:\.\d+)?)\s?(mil|mm)\s*wear|wear[\s-]?layer[:\s]*(\d+(?:\.\d+)?)\s?(mil|mm)/', $text, $m)
                        ? ($m[1] !== '' ? $m[1] . ' ' . $m[2] : $m[3] . ' ' . $m[4]) : null;
                }
                if ($value !== null && $value !== []) {
                    $guessed[$key] = $value;
                }
            }

            if (($guessed['core'] ?? $current['core'] ?? null) === 'dryback') {
                unset($guessed['pad_attached']);   // glue-down vinyl has no attached pad
            }

            if ($guessed) {
                $lineChanges++;
                $rows[] = [$line->id, Str::limit($line->name, 45), $line->productType?->name, collect($guessed)->map(fn ($v, $k) => $k . '=' . implode('/', (array) $v))->implode(', ')];
                if ($apply) {
                    $line->forceFill(['shop_attributes' => ShopAttributes::clean($current + $guessed, $line->productType?->name)])->saveQuietly();
                }
            }

            foreach ($line->productStyles as $style) {
                if ($style->color_tone) {
                    continue;
                }
                $tone = $this->tone(Str::lower(Str::ascii($style->name . ' ' . $style->color)));
                if ($tone) {
                    $toneChanges++;
                    if ($apply) {
                        $style->forceFill(['color_tone' => $tone])->saveQuietly();
                    }
                }
            }

            if ($apply && ($guessed || $line->productStyles->contains(fn ($s) => $s->wasChanged('color_tone')))) {
                app(ShopCacheService::class)->bustProductLine($line->id, $line->product_type_id);
            }
        }

        $this->table(['ID', 'Product line', 'Type', 'Guessed (empty fields only)'], $rows);
        $this->info(($apply ? 'Saved' : 'Would save') . " details on {$lineChanges} product lines and colour tones on {$toneChanges} colours.");
        if (! $apply) {
            $this->comment('Dry run — nothing saved. Run again with --apply to save.');
        }

        return self::SUCCESS;
    }

    private function first(string $key, string $text): ?string
    {
        foreach (self::PATTERNS[$key] ?? [] as $pattern => $value) {
            if (preg_match($pattern, $text, $m)) {
                return str_contains($value, '$1') ? str_replace('$1', $m[1], $value) : $value;
            }
        }

        return null;
    }

    private function installation(string $text): array
    {
        return array_values(array_unique(array_filter(array_map(fn ($p) => preg_match($p, $text) ? self::INSTALL[$p] : null, array_keys(self::INSTALL)))));
    }

    private function tone(string $text): ?string
    {
        foreach (self::TONES as $tone => $pattern) {
            if (preg_match($pattern, $text)) {
                return $tone;
            }
        }

        return null;
    }
}
