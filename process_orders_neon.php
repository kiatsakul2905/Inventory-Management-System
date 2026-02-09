<?php
require_once 'config_neon.php';
checkLogin();

$message = '';

/* ---------------- UPDATE STATUS ---------------- */
if (isset($_GET['update_status'])) {
    $order_id = intval($_GET['update_status']);
    $new_status = $_GET['status'] ?? 'เสร็จสิ้น';

    $stmt = $conn->prepare("UPDATE orders SET status = :status WHERE order_id = :id");
    $stmt->execute([
        'status' => $new_status,
        'id' => $order_id
    ]);

    if ($new_status == 'อนุมัติแล้ว') {
        $stmt = $conn->prepare("
            SELECT product_id, quantity 
            FROM order_details 
            WHERE order_id = :id
        ");
        $stmt->execute(['id' => $order_id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($details as $item) {
            $stmt2 = $conn->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - :qty 
                WHERE product_id = :pid
            ");
            $stmt2->execute([
                'qty' => $item['quantity'],
                'pid' => $item['product_id']
            ]);
        }
    }

    $message = '<div class="alert success">อัปเดตสถานะเรียบร้อยแล้ว</div>';
}

/* ---------------- DELETE ---------------- */
if (isset($_GET['delete'])) {
    $order_id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = :id");
    $stmt->execute(['id' => $order_id]);

    $message = '<div class="alert success">ลบข้อมูลเรียบร้อยแล้ว</div>';
}

/* ---------------- SEARCH ---------------- */
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status_filter'] ?? '';

$whereClause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (o.order_number LIKE :search OR c.customer_name LIKE :search)";
    $params['search'] = "%$search%";
}

if (!empty($statusFilter)) {
    $whereClause .= " AND o.status = :status";
    $params['status'] = $statusFilter;
}

/* ---------------- ORDERS ---------------- */
$sql = "
SELECT o.*, c.customer_name, c.phone,
(SELECT COUNT(*) FROM order_details WHERE order_id = o.order_id) as item_count
FROM orders o
LEFT JOIN customers c ON o.customer_id = c.customer_id
$whereClause
ORDER BY o.order_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt;

/* ---------------- STATS ---------------- */
$stmtStats = $conn->query("
SELECT 
COUNT(*) as total_orders,
SUM(CASE WHEN status = 'รอดำเนินการ' THEN 1 ELSE 0 END) as pending,
SUM(CASE WHEN status = 'อนุมัติแล้ว' THEN 1 ELSE 0 END) as approved,
SUM(CASE WHEN status = 'เสร็จสิ้น' THEN 1 ELSE 0 END) as completed,
SUM(total_amount) as total_sales
FROM orders
");

$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประมวลผลข้อมูลการสั่งสินค้า</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Sarabun', Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .breadcrumb {
            background: white;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .stat-pending { color: #ffc107; }
        .stat-approved { color: #17a2b8; }
        .stat-completed { color: #28a745; }
        .stat-sales { color: #667eea; }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .search-box {
            display: grid;
            grid-template-columns: 1fr 200px auto;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        input, select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: #333; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        
        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚙️ ประมวลผลข้อมูลการสั่งสินค้า</h1>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">🏠 หน้าหลัก</a> / ประมวลผลข้อมูลการสั่งสินค้า
        </div>
        
        <?php echo $message; ?>
        
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-number stat-pending"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">รอดำเนินการ</div>
            </div>
            <div class="stat-box">
                <div class="stat-number stat-approved"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">อนุมัติแล้ว</div>
            </div>
            <div class="stat-box">
                <div class="stat-number stat-completed"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">เสร็จสิ้น</div>
            </div>
            <div class="stat-box">
                <div class="stat-number stat-sales"><?php echo number_format($stats['total_sales'], 0); ?></div>
                <div class="stat-label">ยอดขายรวม (บาท)</div>
            </div>
        </div>
        
        <div class="card">
            <h2>รายการคำสั่งซื้อทั้งหมด</h2>
            
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="ค้นหา เลขที่คำสั่งซื้อ หรือ ชื่อลูกค้า" 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select name="status_filter">
                    <option value="">-- ทุกสถานะ --</option>
                    <option value="รอดำเนินการ" <?php echo $statusFilter=='รอดำเนินการ'?'selected':''; ?>>รอดำเนินการ</option>
                    <option value="อนุมัติแล้ว" <?php echo $statusFilter=='อนุมัติแล้ว'?'selected':''; ?>>อนุมัติแล้ว</option>
                    <option value="เสร็จสิ้น" <?php echo $statusFilter=='เสร็จสิ้น'?'selected':''; ?>>เสร็จสิ้น</option>
                </select>
                <button type="submit" class="btn btn-primary">🔍 ค้นหา</button>
            </form>
            
            <table>
                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>ลูกค้า</th>
                        <th>วันที่สั่ง</th>
                        <th>กำหนดส่ง</th>
                        <th>จำนวนรายการ</th>
                        <th>ยอดรวม</th>
                        <th>สถานะ</th>
                        <th style="width: 250px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $order['order_number']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($order['customer_name']); ?><br>
                            <small style="color: #666;"><?php echo $order['phone']; ?></small>
                        </td>
                        <td><?php echo thaiDate($order['order_date']); ?></td>
                        <td><?php echo thaiDate($order['delivery_date']); ?></td>
                        <td><?php echo $order['item_count']; ?> รายการ</td>
                        <td><?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <span class="status-badge status-<?php 
                                echo $order['status']=='รอดำเนินการ' ? 'pending' : 
                                    ($order['status']=='อนุมัติแล้ว' ? 'approved' : 'completed'); 
                            ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="order_detail.php?id=<?php echo $order['order_id']; ?>" 
                                class="btn btn-info">📄 ดู</a>

                                <?php if ($order['status'] == 'รอดำเนินการ'): ?>
                                <a href="?update_status=<?php echo $order['order_id']; ?>&status=อนุมัติแล้ว" 
                                class="btn btn-success"
                                onclick="return confirm('คุณต้องการอนุมัติคำสั่งซื้อนี้ใช่หรือไม่?')">
                                ✓ อนุมัติ
                                </a>
                                <?php elseif ($order['status'] == 'อนุมัติแล้ว'): ?>
                                <a href="?update_status=<?php echo $order['order_id']; ?>&status=เสร็จสิ้น" 
                                class="btn btn-warning"
                                onclick="return confirm('ยืนยันการส่งสินค้าเสร็จสิ้น?')">
                                ✓ ส่งแล้ว
                                </a>
                                <?php endif; ?>

                                <a href="?delete=<?php echo $order['order_id']; ?>" 
                                class="btn btn-danger"
                                onclick="return confirm('คุณต้องการลบคำสั่งซื้อนี้ใช่หรือไม่?')">
                                🗑️ ลบ
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
