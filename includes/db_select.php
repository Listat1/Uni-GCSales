<?php
// includes/db_select.php
/**
 * Executes a prepared statement and returns all results
 * @param PDO $db The database connection
 * @param string $sql The SQL query with placeholders (:name)
 * @param array $params Associative array of values to bind [:name => value]
 */
function dbSelect($db, $sql, $params = []) {
    $stmt = $db->prepare($sql);
    // Execute Command
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchProducts($db, $q, $cat = '') {
 $sql = "SELECT 
            p.product_id, /* Unique reference ESSENTIAL for BS card expansion */
            p.name,
            p.description, 
            p.price, 
            COALESCE(pi.thumb_path, pi.file_path) AS display_img 
        FROM products p
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.sort_order = 1
            WHERE (p.name LIKE :q1 OR p.description LIKE :q2)";

    $params = [
        ':q1' => "%$q%",
        ':q2' => "%$q%"
    ];

    if (!empty($cat)) {
        $sql .= " AND p.category_id = :cat";
        $params[':cat'] = $cat;
    }

    return dbSelect($db, $sql, $params);
}