<?php

require_once __DIR__ . '/../config/database.php';

class PhilippineLocation
{
    public static function regions(): array
    {
        return getDbConnection()->query(
            'SELECT region_code AS id, region_name AS name FROM ph_regions ORDER BY region_name'
        )->fetchAll();
    }

    public static function provinces(string $regionCode): array
    {
        $statement = getDbConnection()->prepare(
            'SELECT province_id AS id, province_name AS name FROM ph_provinces WHERE region_code=:region ORDER BY province_name'
        );
        $statement->execute(['region' => $regionCode]);
        return $statement->fetchAll();
    }

    public static function localities(int $provinceId): array
    {
        $statement = getDbConnection()->prepare(
            'SELECT locality_id AS id, locality_name AS name FROM ph_localities WHERE province_id=:province ORDER BY locality_name'
        );
        $statement->execute(['province' => $provinceId]);
        return $statement->fetchAll();
    }

    public static function barangays(int $localityId): array
    {
        $statement = getDbConnection()->prepare(
            'SELECT barangay_id AS id, barangay_name AS name FROM ph_barangays WHERE locality_id=:locality ORDER BY barangay_name'
        );
        $statement->execute(['locality' => $localityId]);
        return $statement->fetchAll();
    }

    public static function validHierarchy(string $regionCode, int $provinceId, int $localityId, int $barangayId): bool
    {
        $statement = getDbConnection()->prepare(
            "SELECT COUNT(*)
             FROM ph_barangays b
             JOIN ph_localities l ON l.locality_id=b.locality_id
             JOIN ph_provinces p ON p.province_id=l.province_id
             WHERE b.barangay_id=:barangay AND l.locality_id=:locality
               AND p.province_id=:province AND p.region_code=:region"
        );
        $statement->execute([
            'barangay' => $barangayId, 'locality' => $localityId,
            'province' => $provinceId, 'region' => $regionCode,
        ]);
        return (bool) $statement->fetchColumn();
    }
}

