<?php

require_once __DIR__ . '/../config/database.php';

class Product
{
    public static function featured(int $limit = 4, ?PDO $database = null): array
    {
        $database ??= getDbConnection();
        $limit = max(1, min($limit, 12));
        return $database->query(
            "SELECT p.product_id, p.product_name, p.image_path, c.category_name,
                    MIN(v.price) AS display_price
             FROM products p
             JOIN categories c ON c.category_id = p.category_id
             JOIN product_variants v ON v.product_id = p.product_id AND v.status = 'active'
             WHERE p.status = 'active'
             GROUP BY p.product_id, p.product_name, p.image_path, c.category_name
             ORDER BY p.product_id
             LIMIT {$limit}"
        )->fetchAll();
    }

    public static function catalog(
        int $categoryId = 0,
        string $sort = 'featured',
        ?PDO $database = null,
        string $searchTerm = ''
    ): array
    {
        $database ??= getDbConnection();
        $orderBy = match ($sort) {
            'price_asc' => 'display_price ASC, p.product_name ASC',
            'price_desc' => 'display_price DESC, p.product_name ASC',
            default => 'p.product_id ASC',
        };
        $query = "SELECT p.product_id, p.product_name, p.image_path, c.category_name,
                         MIN(v.price) AS display_price
                  FROM products p
                  JOIN categories c ON c.category_id = p.category_id
                  LEFT JOIN product_variants v ON v.product_id = p.product_id AND v.status = 'active'
                  WHERE p.status = 'active'";
        $parameters = [];
        if ($categoryId > 0) {
            $query .= ' AND p.category_id = :category_id';
            $parameters['category_id'] = $categoryId;
        }
        if ($searchTerm !== '') {
            $query .= ' AND (p.product_name LIKE :search_product OR c.category_name LIKE :search_category)';
            $parameters['search_product'] = '%' . $searchTerm . '%';
            $parameters['search_category'] = '%' . $searchTerm . '%';
        }
        $query .= " GROUP BY p.product_id, p.product_name, p.image_path, c.category_name ORDER BY {$orderBy}";
        $statement = $database->prepare($query);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public static function categories(?PDO $database = null): array
    {
        $database ??= getDbConnection();
        return $database->query(
            "SELECT category_id, category_name FROM categories WHERE status = 'active' ORDER BY category_name"
        )->fetchAll();
    }

    public static function variants(int $productId, ?PDO $database = null): array
    {
        $database ??= getDbConnection();
        $statement = $database->prepare(
            "SELECT p.product_id, p.product_name, p.image_path,
                    v.variant_id, v.size, v.color, v.price, v.stock_quantity
             FROM products p
             JOIN product_variants v ON v.product_id = p.product_id
             WHERE p.product_id = :product_id AND p.status = 'active'
               AND v.status = 'active' AND v.stock_quantity > 0
             ORDER BY v.size, v.color"
        );
        $statement->execute(['product_id' => $productId]);
        return $statement->fetchAll();
    }
}
