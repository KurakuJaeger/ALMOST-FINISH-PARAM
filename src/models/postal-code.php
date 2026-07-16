<?php

require_once __DIR__ . '/../config/database.php';

class PostalCode
{
    private static function normalize(string $value): string
    {
        $value = mb_strtoupper(trim($value), 'UTF-8');
        $value = str_replace(['CITY OF ', ' CITY', 'MUNICIPALITY OF ', 'PROVINCE OF ', ' PROVINCE'], '', $value);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $ascii === false ? $value : $ascii;
        return trim(preg_replace('/[^A-Z0-9]+/', ' ', $value) ?? '');
    }

    private static function items(): array
    {
        static $items = null;
        if ($items !== null) {
            return $items;
        }

        $file = dirname(__DIR__, 2) . '/db/data/philippine_postal_codes.json';
        $payload = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        return $items = $payload['items'] ?? [];
    }

    public static function forAddress(int $localityId, int $barangayId = 0): array
    {
        $statement = getDbConnection()->prepare(
            'SELECT l.locality_id, l.locality_name, p.province_name, r.region_name,
                    b.barangay_name
             FROM ph_localities l
             JOIN ph_provinces p ON p.province_id=l.province_id
             JOIN ph_regions r ON r.region_code=p.region_code
             LEFT JOIN ph_barangays b ON b.locality_id=l.locality_id AND b.barangay_id=:barangay
             WHERE l.locality_id=:locality'
        );
        $statement->execute(['locality' => $localityId, 'barangay' => $barangayId]);
        $address = $statement->fetch();
        if (!$address) {
            return ['items' => [], 'recommended' => null, 'match' => 'none'];
        }

        $locality = self::normalize($address['locality_name']);
        $province = self::normalize($address['province_name']);
        $barangay = self::normalize((string) ($address['barangay_name'] ?? ''));
        $isNcr = str_contains(self::normalize($address['region_name']), 'NATIONAL CAPITAL')
            || str_contains($province, 'NATIONAL CAPITAL');

        $matches = [];
        foreach (self::items() as $item) {
            $location = self::normalize((string) ($item['location'] ?? ''));
            $area = self::normalize((string) ($item['area'] ?? ''));
            $matchesLocation = $isNcr
                ? $location === $locality
                : $location === $province && ($area === $locality || str_starts_with($area, $locality . ' '));
            if (!$matchesLocation) {
                continue;
            }

            $matches[] = [
                'code' => (string) $item['postal_code'],
                'label' => (string) $item['area'],
                'area_normalized' => $area,
            ];
        }

        $unique = [];
        foreach ($matches as $match) {
            $unique[$match['code'] . '|' . $match['label']] = $match;
        }
        $matches = array_values($unique);

        $recommended = null;
        $matchType = 'multiple';
        if (count($matches) === 1) {
            $recommended = $matches[0]['code'];
            $matchType = 'locality';
        } elseif ($barangay !== '') {
            $barangayMatches = array_values(array_filter($matches, static function (array $match) use ($barangay): bool {
                $area = $match['area_normalized'];
                return $area === $barangay || str_starts_with($area, $barangay . ' ') || str_starts_with($barangay, $area . ' ');
            }));
            $codes = array_values(array_unique(array_column($barangayMatches, 'code')));
            if (count($codes) === 1) {
                $recommended = $codes[0];
                $matchType = 'barangay';
            }
        }

        if (!$matches) {
            $matchType = 'none';
        }

        return [
            'items' => array_map(static fn (array $item): array => [
                'code' => $item['code'], 'label' => $item['label'],
            ], $matches),
            'recommended' => $recommended,
            'match' => $matchType,
        ];
    }
}
