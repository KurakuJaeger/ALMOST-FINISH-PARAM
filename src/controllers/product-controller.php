<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/audit-log-service.php';
require_once __DIR__ . '/../services/product-image-service.php';

// Backs the "Stocks and Prices" and "Remaining Inventory" sections.
// Each dashboard product becomes one product row and one default variant.
class ProductController
{
    public static function listStock(): array
    {
        $stmt = getDbConnection()->query(
            "SELECT p.product_id, p.product_name, p.image_path, c.category_name,
                    v.variant_id, v.size, v.color, v.price, v.stock_quantity,
                    (SELECT COUNT(*) FROM product_variants all_variants
                     WHERE all_variants.product_id = p.product_id) AS product_variant_count
             FROM products p
             JOIN categories c ON p.category_id = c.category_id
             JOIN product_variants v ON v.product_id = p.product_id
             ORDER BY p.created_at DESC, p.product_id DESC, v.color, v.size"
        );
        return $stmt->fetchAll();
    }

    public static function inventoryReport(): array
    {
        $stmt = getDbConnection()->query(
            "SELECT p.product_name, c.category_name, v.size, v.color, v.stock_quantity,
                    v.price, (v.stock_quantity * v.price) AS total_value
             FROM products p
             JOIN categories c ON p.category_id = c.category_id
             JOIN product_variants v ON v.product_id = p.product_id
             ORDER BY p.product_name, v.color, v.size"
        );
        return $stmt->fetchAll();
    }

    public static function summary(): array
    {
        $db = getDbConnection();

        $totalProducts = (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $totalVariants = (int) $db->query('SELECT COUNT(*) FROM product_variants')->fetchColumn();
        $totalStock    = (int) $db->query('SELECT COALESCE(SUM(stock_quantity), 0) FROM product_variants')->fetchColumn();
        $lowStock      = (int) $db->query('SELECT COUNT(*) FROM product_variants WHERE stock_quantity <= 5')->fetchColumn();
        $inventoryValue = (float) $db->query('SELECT COALESCE(SUM(stock_quantity * price), 0) FROM product_variants')->fetchColumn();

        return [
            'total_products'  => $totalProducts,
            'total_variants'  => $totalVariants,
            'total_stock'     => $totalStock,
            'low_stock'       => $lowStock,
            'inventory_value' => round($inventoryValue, 2),
        ];
    }

    public static function createStockItem(array $input, int $actorId): array
    {
        self::validateInput($input);
        $db = getDbConnection();
        $db->beginTransaction();

        try {
            $categoryId = self::findOrCreateCategory($input['category'] ?? 'Uncategorized');

            $stmt = $db->prepare(
                'INSERT INTO products (category_id, product_name, image_path, status)
                 VALUES (:cat, :name, :image_path, :status)'
            );
            $stmt->execute([
                'cat'    => $categoryId,
                'name'   => $input['name'] ?? '',
                'image_path' => $input['image_path'] ?? null,
                'status' => 'active',
            ]);
            $productId = (int) $db->lastInsertId();

            $stmt = $db->prepare(
                'INSERT INTO product_variants (product_id, size, color, price, stock_quantity)
                 VALUES (:product_id, :size, :color, :price, :stock)'
            );
            $stmt->execute([
                'product_id' => $productId,
                'size'       => trim((string) $input['size']),
                'color'      => trim((string) $input['color']),
                'price'      => $input['price'] ?? 0,
                'stock'      => $input['stock'] ?? 0,
            ]);

            AuditLogService::record(
                $actorId,
                'product.create',
                'products',
                $productId,
                'Created product: ' . $input['name'],
                $db
            );
            $db->commit();
            return ['product_id' => $productId];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            http_response_code(500);
            return ['error' => $e->getMessage()];
        }
    }

    public static function updateVariant(int $variantId, array $input, int $actorId): array
    {
        self::validateInput($input);
        $productId = (int) ($input['product_id'] ?? 0);
        if ($productId < 1) {
            throw new InvalidArgumentException('A valid product is required');
        }

        $db = getDbConnection();
        $db->beginTransaction();
        try {
            $existingVariant = $db->prepare(
                'SELECT 1 FROM product_variants
                 WHERE variant_id = :variant_id AND product_id = :product_id'
            );
            $existingVariant->execute([
                'variant_id' => $variantId,
                'product_id' => $productId,
            ]);
            if (!$existingVariant->fetchColumn()) {
                throw new InvalidArgumentException('Product variant not found');
            }

            $categoryId = self::findOrCreateCategory($input['category']);
            $product = $db->prepare(
                'UPDATE products SET product_name = :name, category_id = :category
                 WHERE product_id = :product_id'
            );
            $product->execute([
                'name' => $input['name'],
                'category' => $categoryId,
                'product_id' => $productId,
            ]);

            $variant = $db->prepare(
                'UPDATE product_variants
                 SET size = :size, color = :color, price = :price, stock_quantity = :stock
                 WHERE variant_id = :variant_id AND product_id = :product_id'
            );
            $variant->execute([
                'size' => trim((string) $input['size']),
                'color' => trim((string) $input['color']),
                'price' => $input['price'],
                'stock' => $input['stock'],
                'variant_id' => $variantId,
                'product_id' => $productId,
            ]);
            AuditLogService::record(
                $actorId,
                'product_variant.update',
                'product_variants',
                $variantId,
                sprintf(
                    'Updated %s - %s / %s (PHP %.2f, stock %d)',
                    $input['name'],
                    $input['color'],
                    $input['size'],
                    $input['price'],
                    $input['stock']
                ),
                $db
            );
            $db->commit();
            return ['success' => true];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public static function deleteStockItem(int $productId, int $actorId): array
    {
        $lookup = getDbConnection()->prepare('SELECT product_name, image_path FROM products WHERE product_id = :id');
        $lookup->execute(['id' => $productId]);
        $product = $lookup->fetch();
        if (!$product) { http_response_code(404); return ['error' => 'Product not found']; }
        $stmt = getDbConnection()->prepare('DELETE FROM products WHERE product_id = :id');
        $stmt->execute(['id' => $productId]);
        ProductImageService::deleteManaged($product['image_path'] ?? null);
        AuditLogService::record(
            $actorId,
            'product.delete',
            'products',
            $productId,
            'Deleted product: ' . $product['product_name']
        );
        return ['success' => true];
    }

    private static function validateInput(array $input): void
    {
        if (trim((string) ($input['name'] ?? '')) === '' || trim((string) ($input['category'] ?? '')) === '' ||
            trim((string) ($input['size'] ?? '')) === '' || trim((string) ($input['color'] ?? '')) === '' ||
            !is_numeric($input['price'] ?? null) || (float) $input['price'] < 0 ||
            filter_var($input['stock'] ?? null, FILTER_VALIDATE_INT) === false || (int) $input['stock'] < 0) {
            throw new InvalidArgumentException(
                'Valid product name, category, size, color, price, and stock are required'
            );
        }
    }

    private static function findOrCreateCategory(string $categoryName): int
    {
        $db = getDbConnection();

        $stmt = $db->prepare('SELECT category_id FROM categories WHERE category_name = :name LIMIT 1');
        $stmt->execute(['name' => $categoryName]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            return (int) $existing;
        }

        $stmt = $db->prepare('INSERT INTO categories (category_name) VALUES (:name)');
        $stmt->execute(['name' => $categoryName]);
        return (int) $db->lastInsertId();
    }
}
