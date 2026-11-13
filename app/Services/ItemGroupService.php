<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Item;
use App\Models\ItemGroup;
use Illuminate\Support\Collection;

class ItemGroupService
{
    /**
     * Kelompokkan daftar barang Master Aset berdasarkan kelompok terdaftar (`item_groups`), kolom `category`
     * (misal: TPLINK, PC, MIKROTIK), atau fallback nama dasar unit (misal: TPLINK Router 14 & 17 -> TPLINK Router).
     *
     * @param  Collection<int, Item>  $items
     * @return array{
     *     groups: array<int, array<string, mixed>>,
     *     item_to_group: array<int, string>
     * }
     */
    public function buildGroups(
        Collection $items,
        bool $includeBrandRollups = false,
        bool $includeRegisteredGroups = true
    ): array {
        $primaryBuckets = [];
        $brandBuckets = [];

        if ($includeRegisteredGroups) {
            foreach (ItemGroup::query()->orderBy('name')->get() as $registeredGroup) {
                $cleanName = $this->cleanCategory($registeredGroup->name);
                if ($cleanName === null) {
                    continue;
                }

                $bucketKey = $this->canonicalKey($cleanName);
                $primaryBuckets[$bucketKey] = [
                    'explicit' => true,
                    'proposed_label' => $cleanName,
                    'items' => collect(),
                ];
            }
        }

        foreach ($items as $item) {
            $explicitCategory = $this->cleanCategory($item->category);

            if ($explicitCategory !== null) {
                $bucketKey = $this->canonicalKey($explicitCategory);
                $isExplicit = true;
                $proposedLabel = $explicitCategory;
            } else {
                $baseName = $this->normalizeItemBaseName($item->name);
                $bucketKey = $this->canonicalKey($baseName);
                $isExplicit = false;
                $proposedLabel = $baseName;
            }

            if (! isset($primaryBuckets[$bucketKey])) {
                $primaryBuckets[$bucketKey] = [
                    'explicit' => $isExplicit,
                    'proposed_label' => $proposedLabel,
                    'items' => collect(),
                ];
            } elseif ($isExplicit && ! $primaryBuckets[$bucketKey]['explicit']) {
                // Jika ada item dengan kategori eksplisit pada key yang sama, prioritaskan label eksplisit tersebut
                $primaryBuckets[$bucketKey]['explicit'] = true;
                $primaryBuckets[$bucketKey]['proposed_label'] = $proposedLabel;
            }

            $primaryBuckets[$bucketKey]['items']->push($item);

            if ($includeBrandRollups) {
                $brandInfo = $this->extractBrandToken($explicitCategory ?? $item->name);
                if ($brandInfo !== null) {
                    if (! isset($brandBuckets[$brandInfo['key']])) {
                        $brandBuckets[$brandInfo['key']] = [
                            'label' => $brandInfo['label'],
                            'items' => collect(),
                        ];
                    }
                    $brandBuckets[$brandInfo['key']]['items']->push($item);
                }
            }
        }

        $groups = [];
        $itemToGroup = [];
        $exactSignatures = [];

        foreach ($primaryBuckets as $bucketKey => $bucketData) {
            /** @var Collection<int, Item> $bucket */
            $bucket = $bucketData['items'];
            $groupKey = 'name:'.$bucketKey;

            $label = ($bucketData['explicit'] || $bucket->count() > 1)
                ? $bucketData['proposed_label']
                : ($bucket->first()?->name ?? $bucketData['proposed_label']);

            $itemIds = $bucket->pluck('id')->sort()->values()->all();
            if (! empty($itemIds)) {
                $exactSignatures[implode(',', $itemIds)] = true;
            }

            foreach ($itemIds as $itemId) {
                $itemToGroup[$itemId] = $groupKey;
            }

            $groups[] = $this->formatGroupEntry(
                key: $groupKey,
                slug: $bucketKey,
                kind: 'name',
                label: $label,
                isExplicitCategory: (bool) $bucketData['explicit'],
                bucket: $bucket
            );
        }

        if ($includeBrandRollups) {
            foreach ($brandBuckets as $brandKey => $brandData) {
                /** @var Collection<int, Item> $bucket */
                $bucket = $brandData['items'];
                if ($bucket->count() < 2) {
                    continue;
                }

                $itemIds = $bucket->pluck('id')->sort()->values()->all();
                $signature = implode(',', $itemIds);

                if (isset($exactSignatures[$signature])) {
                    continue;
                }

                $exactSignatures[$signature] = true;

                $groups[] = $this->formatGroupEntry(
                    key: 'brand:'.$brandKey,
                    slug: 'brand-'.$brandKey,
                    kind: 'brand',
                    label: 'Semua '.$brandData['label'],
                    isExplicitCategory: false,
                    bucket: $bucket
                );
            }
        }

        usort($groups, function (array $a, array $b) {
            if ($a['kind'] !== $b['kind']) {
                return $a['kind'] === 'name' ? -1 : 1;
            }

            return strcasecmp($a['label'], $b['label']);
        });

        return [
            'groups' => $groups,
            'item_to_group' => $itemToGroup,
        ];
    }

    /**
     * Cari grup dari query parameter (`name:...`, `brand:...`, slug canonical, atau nama kategori).
     *
     * @param  array<int, array<string, mixed>>  $groups
     * @return array<string, mixed>|null
     */
    public function findGroup(array $groups, ?string $query): ?array
    {
        $needle = trim((string) $query);
        if ($needle === '') {
            return null;
        }

        $needleLower = mb_strtolower($needle);
        $needleCanonical = $this->canonicalKey($needle);

        foreach ($groups as $group) {
            if (mb_strtolower((string) $group['key']) === $needleLower) {
                return $group;
            }
        }

        foreach ($groups as $group) {
            if (
                $group['slug'] === $needleLower
                || $this->canonicalKey((string) $group['label']) === $needleCanonical
            ) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Daftar saran label kategori / kelompok unik yang sudah ada di Master Aset.
     *
     * @return array<int, string>
     */
    public function distinctCategorySuggestions(): array
    {
        $registered = ItemGroup::query()
            ->orderBy('name')
            ->pluck('name');

        $explicit = Item::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return $registered
            ->concat($explicit)
            ->map(fn ($v) => $this->cleanCategory($v))
            ->filter()
            ->unique(fn ($v) => $this->canonicalKey($v))
            ->sort(fn ($a, $b) => strcasecmp($a, $b))
            ->values()
            ->all();
    }

    public function cleanCategory(?string $category): ?string
    {
        $cleaned = trim((string) preg_replace('/\s+/', ' ', (string) $category));

        return $cleaned !== '' ? $cleaned : null;
    }

    public function normalizeItemBaseName(string $name): string
    {
        $cleaned = trim(preg_replace('/\s+/', ' ', $name) ?? $name);
        $stripped = preg_replace(
            '/(?:[\s_-]+(?:unit|no\.?|#)?[\s_-]*#?\d{1,4}|\s*\(\d{1,4}\))$/i',
            '',
            $cleaned
        );
        $stripped = trim((string) $stripped, " \t\n\r\0\x0B-_#");

        return $stripped !== '' ? $stripped : $cleaned;
    }

    public function canonicalKey(string $value): string
    {
        $normalized = preg_replace('/[^a-z0-9]/', '', mb_strtolower(trim($value))) ?? '';

        return $normalized !== '' ? $normalized : mb_strtolower(trim($value));
    }

    /**
     * @return array{key: string, label: string}|null
     */
    private function extractBrandToken(string $name): ?array
    {
        $trimmed = trim(preg_replace('/\s+/', ' ', $name) ?? $name);
        $parts = preg_split('/\s+/', $trimmed);
        $firstWord = $parts[0] ?? '';

        if ($firstWord === '') {
            return null;
        }

        $key = preg_replace('/[^a-z0-9]/', '', mb_strtolower($firstWord)) ?? '';

        if (mb_strlen($key) < 3) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $firstWord,
        ];
    }

    /**
     * @param  Collection<int, Item>  $bucket
     * @return array<string, mixed>
     */
    private function formatGroupEntry(
        string $key,
        string $slug,
        string $kind,
        string $label,
        bool $isExplicitCategory,
        Collection $bucket
    ): array {
        $availableCount = $bucket->where('status', ItemStatus::TERSEDIA)->count();
        $borrowedCount = $bucket->where('status', ItemStatus::DIPINJAM)->count();
        $maintenanceCount = $bucket->where('status', ItemStatus::UNDER_MAINTENANCE)->count();

        $asetCount = $bucket->where('type', ItemType::ASET)->count();
        $bhpCount = $bucket->where('type', ItemType::BAHAN_HABIS_PAKAI)->count();

        $locations = $bucket
            ->pluck('location')
            ->filter(fn ($loc) => filled($loc))
            ->map(fn ($loc) => trim((string) $loc))
            ->unique()
            ->values()
            ->all();

        $unscheduledCount = $bucket
            ->filter(fn (Item $i) => $i->relationLoaded('preventiveSchedules') && $i->preventiveSchedules->isEmpty())
            ->count();

        return [
            'key' => $key,
            'slug' => $slug,
            'kind' => $kind,
            'label' => $label,
            'is_explicit_category' => $isExplicitCategory,
            'item_ids' => $bucket->pluck('id')->values()->all(),
            'item_count' => $bucket->count(),
            'total_units' => (int) $bucket->sum(fn (Item $i) => max(1, (int) $i->stock)),
            'available_count' => $availableCount,
            'borrowed_count' => $borrowedCount,
            'maintenance_count' => $maintenanceCount,
            'aset_count' => $asetCount,
            'bhp_count' => $bhpCount,
            'unscheduled_count' => $unscheduledCount,
            'locations' => $locations,
            'preview_codes' => $bucket->take(4)->pluck('item_code')->values()->all(),
            'items' => $bucket,
        ];
    }
}
