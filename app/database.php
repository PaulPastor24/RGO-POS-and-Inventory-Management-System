<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (DB_DRIVER === 'mysql') {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            MYSQL_HOST,
            MYSQL_PORT,
            MYSQL_DATABASE,
            MYSQL_CHARSET
        );
        $pdo = new PDO($dsn, MYSQL_USERNAME, MYSQL_PASSWORD);
    } else {
        if (!is_dir(APP_DATA_DIR)) {
            mkdir(APP_DATA_DIR, 0777, true);
        }
        $pdo = new PDO('sqlite:' . SQLITE_DB_FILE);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    initializeDatabase($pdo);

    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    if (isMySQL($pdo)) {
        initializeMySQLDatabase($pdo);
    } else {
        initializeSQLiteDatabase($pdo);
    }

    ensureCatalogSeed($pdo);
    ensureInventoryRows($pdo);
}

function isMySQL(PDO $pdo): bool
{
    return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
}

function initializeSQLiteDatabase(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            category TEXT NOT NULL,
            price REAL NOT NULL,
            image_path TEXT,
            is_active INTEGER NOT NULL DEFAULT 1
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_code TEXT NOT NULL UNIQUE,
            full_name TEXT NOT NULL,
            student_no TEXT NOT NULL,
            campus TEXT NOT NULL,
            contact_no TEXT NOT NULL,
            notes TEXT,
            status TEXT NOT NULL DEFAULT "Pending",
            total_amount REAL NOT NULL,
            payment_method TEXT,
            created_at TEXT NOT NULL,
            updated_at TEXT
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            product_name TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price REAL NOT NULL,
            line_total REAL NOT NULL,
            FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS app_meta (
            meta_key TEXT PRIMARY KEY,
            meta_value TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS inventory (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL UNIQUE,
            quantity_on_hand INTEGER NOT NULL DEFAULT 0,
            reorder_point INTEGER NOT NULL DEFAULT 10,
            reorder_quantity INTEGER NOT NULL DEFAULT 50,
            unit_cost REAL,
            warehouse_location TEXT,
            last_counted_at TEXT,
            FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS stock_movements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            movement_type TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            prior_quantity INTEGER NOT NULL,
            reason TEXT,
            reference_id INTEGER,
            recorded_by TEXT,
            created_at TEXT NOT NULL,
            FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS order_status_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            old_status TEXT,
            new_status TEXT NOT NULL,
            changed_by TEXT,
            reason TEXT,
            created_at TEXT NOT NULL,
            FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entity_type TEXT NOT NULL,
            entity_id INTEGER NOT NULL,
            action TEXT NOT NULL,
            field_name TEXT,
            old_value TEXT,
            new_value TEXT,
            user_email TEXT,
            user_role TEXT,
            created_at TEXT NOT NULL
        )'
    );

     
    foreach (['image_path TEXT', 'payment_method TEXT', 'updated_at TEXT'] as $columnSql) {
        try {
            if (str_contains($columnSql, 'image_path')) {
                $pdo->exec('ALTER TABLE products ADD COLUMN ' . $columnSql);
            } else {
                $pdo->exec('ALTER TABLE orders ADD COLUMN ' . $columnSql);
            }
        } catch (Throwable $e) {
             
        }
    }
}

function initializeMySQLDatabase(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(120) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            image_path VARCHAR(500) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_code VARCHAR(80) NOT NULL UNIQUE,
            full_name VARCHAR(255) NOT NULL,
            student_no VARCHAR(80) NOT NULL,
            campus VARCHAR(120) NOT NULL,
            contact_no VARCHAR(80) NOT NULL,
            notes TEXT,
            status VARCHAR(60) NOT NULL DEFAULT "Pending",
            total_amount DECIMAL(12,2) NOT NULL,
            payment_method VARCHAR(60) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            line_total DECIMAL(12,2) NOT NULL,
            FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS app_meta (
            meta_key VARCHAR(100) PRIMARY KEY,
            meta_value TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS inventory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL UNIQUE,
            quantity_on_hand INT NOT NULL DEFAULT 0,
            reorder_point INT NOT NULL DEFAULT 10,
            reorder_quantity INT NOT NULL DEFAULT 50,
            unit_cost DECIMAL(10,2) NULL,
            warehouse_location VARCHAR(255) NULL,
            last_counted_at DATETIME NULL,
            FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS stock_movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            movement_type VARCHAR(40) NOT NULL,
            quantity INT NOT NULL,
            prior_quantity INT NOT NULL,
            reason TEXT NULL,
            reference_id INT NULL,
            recorded_by VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS order_status_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            old_status VARCHAR(60) NULL,
            new_status VARCHAR(60) NOT NULL,
            changed_by VARCHAR(255) NULL,
            reason TEXT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(60) NOT NULL,
            entity_id INT NOT NULL,
            action VARCHAR(60) NOT NULL,
            field_name VARCHAR(120) NULL,
            old_value TEXT NULL,
            new_value TEXT NULL,
            user_email VARCHAR(255) NULL,
            user_role VARCHAR(60) NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function ensureCatalogSeed(PDO $pdo): void
{
    $catalogVersion = 'catalog_v4_pos_inventory_20260311';
    $currentVersion = getMetaValue($pdo, 'catalog_version');

    if ($currentVersion === $catalogVersion) {
        return;
    }

    $seed = [
        ['ID Lace', 'Accessories', 60.00, '/CAPSTONE/img/products/ID.jpg'],
        ['Collar Pin', 'Accessories', 100.00, '/CAPSTONE/img/products/Collar%20Pin.jpg'],
        ['White Fabric Embroidered', 'Fabric', 150.00, '/CAPSTONE/img/products/blouse.png'],
        ['Checkered (for College Skirts)', 'Fabric', 150.00, '/CAPSTONE/img/products/Skirts.png'],
        ['Checkered (for College Pants)', 'Fabric', 150.00, '/CAPSTONE/img/products/pants.png'],
    ];

    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM products');

        $stmt = $pdo->prepare(
            'INSERT INTO products (name, category, price, image_path, is_active)
             VALUES (:name, :category, :price, :image_path, 1)'
        );

        foreach ($seed as $row) {
            $stmt->execute([
                ':name' => $row[0],
                ':category' => $row[1],
                ':price' => $row[2],
                ':image_path' => $row[3],
            ]);
        }

        setMetaValue($pdo, 'catalog_version', $catalogVersion);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function ensureInventoryRows(PDO $pdo): void
{
    $products = $pdo->query('SELECT id FROM products')->fetchAll();
    $insert = $pdo->prepare(
        'INSERT INTO inventory (product_id, quantity_on_hand, reorder_point, reorder_quantity, last_counted_at)
         VALUES (:product_id, :quantity_on_hand, :reorder_point, :reorder_quantity, :last_counted_at)'
    );

    foreach ($products as $product) {
        $check = $pdo->prepare('SELECT 1 FROM inventory WHERE product_id = :product_id');
        $check->execute([':product_id' => (int) $product['id']]);
        if ($check->fetchColumn()) {
            continue;
        }

        $insert->execute([
            ':product_id' => (int) $product['id'],
            ':quantity_on_hand' => 100,
            ':reorder_point' => 10,
            ':reorder_quantity' => 50,
            ':last_counted_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

function getMetaValue(PDO $pdo, string $key): string
{
    $stmt = $pdo->prepare('SELECT meta_value FROM app_meta WHERE meta_key = :key');
    $stmt->execute([':key' => $key]);
    return (string) ($stmt->fetchColumn() ?: '');
}

function setMetaValue(PDO $pdo, string $key, string $value): void
{
    if (isMySQL($pdo)) {
        $stmt = $pdo->prepare(
            'INSERT INTO app_meta (meta_key, meta_value) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)'
        );
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO app_meta (meta_key, meta_value) VALUES (:key, :value)
             ON CONFLICT(meta_key) DO UPDATE SET meta_value = excluded.meta_value'
        );
    }

    $stmt->execute([
        ':key' => $key,
        ':value' => $value,
    ]);
}

function recordOrderStatusHistory(
    PDO $pdo,
    int $orderId,
    ?string $oldStatus,
    string $newStatus,
    ?string $changedBy,
    ?string $reason
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, reason, created_at)
         VALUES (:order_id, :old_status, :new_status, :changed_by, :reason, :created_at)'
    );
    $stmt->execute([
        ':order_id' => $orderId,
        ':old_status' => $oldStatus,
        ':new_status' => $newStatus,
        ':changed_by' => $changedBy,
        ':reason' => $reason,
        ':created_at' => date('Y-m-d H:i:s'),
    ]);
}

function recordAuditLog(
    PDO $pdo,
    string $entityType,
    int $entityId,
    string $action,
    ?string $fieldName,
    ?string $oldValue,
    ?string $newValue,
    ?string $userEmail,
    ?string $userRole
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO audit_log (entity_type, entity_id, action, field_name, old_value, new_value, user_email, user_role, created_at)
         VALUES (:entity_type, :entity_id, :action, :field_name, :old_value, :new_value, :user_email, :user_role, :created_at)'
    );
    $stmt->execute([
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':action' => $action,
        ':field_name' => $fieldName,
        ':old_value' => $oldValue,
        ':new_value' => $newValue,
        ':user_email' => $userEmail,
        ':user_role' => $userRole,
        ':created_at' => date('Y-m-d H:i:s'),
    ]);
}
