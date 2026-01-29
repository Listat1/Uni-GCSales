<?php
// includes/db_select.php
/**
 * General prepared statement execution, returns all results
 * @param PDO $db The database connection
 * @param string $sql query with placeholders (:name)
 * @param array $params Associative array of values to bind [:name => value]
 */
function dbSelect($db, $sql, $params = []) {
    $stmt = $db->prepare($sql);
    // Execute Command
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Get list of products from selected category that match search string
// Used by JS:Livesearch
function searchProducts($db, $q, $cat = '') {
$sql = "SELECT 
            p.product_id,
            p.name,
            p.description, 
            p.price, 
            COALESCE(pi.thumb_path, pi.file_path) AS display_img 
        FROM products p
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.sort_order = 1
            WHERE p.status_id = 1 
            AND (p.name LIKE :q1 OR p.description LIKE :q2)";

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
/**
 * Get all orders placed by a specific user (The Buyer)
 */
function getPurchaseHistory($db, $userID) {
    $sql = "SELECT 
                o.order_id, 
                o.created_at, 
                o.total_amount, 
                p.name AS product_name, 
                p.price,
                pi.thumb_path
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.sort_order = 1
            WHERE o.user_id = :uid
            ORDER BY o.created_at DESC";
            
    return dbSelect($db, $sql, [':uid' => $userID]);
}
/**
 * Get all products owned by this user that have been ordered
 */
function getSoldItems($db, $userID) {
    $sql = "SELECT 
                p.product_id, 
                p.name, 
                p.price, 
                o.created_at AS sale_date,
                o.order_id,
                u.username AS buyer_name
            FROM products p
            JOIN order_items oi ON p.product_id = oi.product_id
            JOIN orders o ON oi.order_id = o.order_id
            JOIN users u ON o.user_id = u.user_id
            WHERE p.seller_id = :sid
            ORDER BY o.created_at DESC";
            
    return dbSelect($db, $sql, [':sid' => $userID]);
}