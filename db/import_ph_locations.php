<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/config/database.php';

$source = __DIR__ . '/data/philippine_locations_2019.json';
if (!is_readable($source)) {
    throw new RuntimeException('Philippine location dataset is missing: ' . $source);
}

$locations = json_decode(file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);
$database = getDbConnection();
$database->beginTransaction();

try {
    $regionStatement = $database->prepare(
        "INSERT INTO ph_regions (region_code, region_name)
         VALUES (:code, :name)
         ON DUPLICATE KEY UPDATE region_name = VALUES(region_name)"
    );
    $provinceStatement = $database->prepare(
        "INSERT INTO ph_provinces (region_code, province_name)
         VALUES (:region_code, :name)
         ON DUPLICATE KEY UPDATE province_id = LAST_INSERT_ID(province_id), province_name = VALUES(province_name)"
    );
    $localityStatement = $database->prepare(
        "INSERT INTO ph_localities (province_id, locality_name)
         VALUES (:province_id, :name)
         ON DUPLICATE KEY UPDATE locality_id = LAST_INSERT_ID(locality_id), locality_name = VALUES(locality_name)"
    );
    $barangayStatement = $database->prepare(
        "INSERT IGNORE INTO ph_barangays (locality_id, barangay_name)
         VALUES (:locality_id, :name)"
    );

    $counts = ['regions' => 0, 'provinces' => 0, 'localities' => 0, 'barangays' => 0];
    foreach ($locations as $regionCode => $region) {
        $regionStatement->execute(['code' => $regionCode, 'name' => $region['region_name']]);
        $counts['regions']++;

        foreach ($region['province_list'] as $provinceName => $province) {
            $provinceStatement->execute(['region_code' => $regionCode, 'name' => $provinceName]);
            $provinceId = (int) $database->lastInsertId();
            $counts['provinces']++;

            foreach ($province['municipality_list'] as $localityName => $locality) {
                $localityStatement->execute(['province_id' => $provinceId, 'name' => $localityName]);
                $localityId = (int) $database->lastInsertId();
                $counts['localities']++;

                foreach ($locality['barangay_list'] as $barangayName) {
                    $barangayStatement->execute(['locality_id' => $localityId, 'name' => $barangayName]);
                    $counts['barangays']++;
                }
            }
        }
    }

    $database->commit();
    echo sprintf(
        "Imported %d regions, %d province/district groups, %d cities/municipalities, and %d barangays.\n",
        $counts['regions'], $counts['provinces'], $counts['localities'], $counts['barangays']
    );
} catch (Throwable $exception) {
    if ($database->inTransaction()) {
        $database->rollBack();
    }
    throw $exception;
}

