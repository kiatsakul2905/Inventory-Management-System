<?php
require_once 'config_neon.php';
checkLogin();

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to'] ?? date('Y-m-t');
$status   = $_GET['status'] ?? '';

$whereClause = "WHERE o.delivery_date BETWEEN :dateFrom AND :dateTo";
$params = [
    'dateFrom' => $dateFrom,
    'dateTo' => $dateTo
];

if (!empty($status)) {
    $whereClause .= " AND o.status = :status";
    $params['status'] = $status;
}

$sql = "SELECT o.*, c.customer_name, c.address, c.phone,
        (SELECT COUNT(*) FROM order_details WHERE order_id = o.order_id) as item_count
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        $whereClause
        ORDER BY o.delivery_date ASC, o.order_number ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* summary */
$totalOrders = 0;
$totalAmount = 0;
$overdueCount = 0;
$todayCount = 0;
$today = date('Y-m-d');

foreach ($orders as $row) {
    $totalOrders++;
    $totalAmount += $row['total_amount'];

    if ($row['delivery_date'] < $today && $row['status'] != 'เสร็จสิ้น') {
        $overdueCount++;
    }

    if ($row['delivery_date'] == $today) {
        $todayCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานกำหนดส่งสินค้า</title>
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
        
        .filter-box {
            display: grid;
            grid-template-columns: 1fr 1fr 200px auto;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        input, select {
            width: 100%;
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
        }
        
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        
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
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        
        .overdue {
            background: #f8d7da !important;
        }
        
        .today {
            background: #fff3cd !important;
        }
        
        .summary-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-number {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        
        .summary-label {
            color: #666;
            font-size: 14px;
        }
        
        .print-btn {
            margin-bottom: 20px;
        }
        
        @media print {
            .header, .breadcrumb, .filter-box, .print-btn {
                display: none;
            }
            
            body {
                background: white;
            }
            
            .card {
                box-shadow: none;
            }
        }
    </style>
    <script>
        function printReport() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>📊 รายงานกำหนดส่งสินค้า</h1>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">🏠 หน้าหลัก</a> / รายงานกำหนดส่งสินค้า
        </div>
        
        <div class="card">
            <h2>เงื่อนไขการค้นหา</h2>
            
            <form method="GET" class="filter-box">
                <div>
                    <label>จากวันที่:</label>
                    <input type="date" name="date_from" value="<?php echo $dateFrom; ?>" required>
                </div>
                <div>
                    <label>ถึงวันที่:</label>
                    <input type="date" name="date_to" value="<?php echo $dateTo; ?>" required>
                </div>
                <div>
                    <label>สถานะ:</label>
                    <select name="status">
                        <option value="">ทั้งหมด</option>
                        <option value="รอดำเนินการ" <?php echo $status=='รอดำเนินการ'?'selected':''; ?>>รอดำเนินการ</option>
                        <option value="อนุมัติแล้ว" <?php echo $status=='อนุมัติแล้ว'?'selected':''; ?>>อนุมัติแล้ว</option>
                        <option value="เสร็จสิ้น" <?php echo $status=='เสร็จสิ้น'?'selected':''; ?>>เสร็จสิ้น</option>
                    </select>
                </div>
                <div style="align-self: end;">
                    <button type="submit" class="btn btn-primary">🔍 ค้นหา</button>
                </div>
            </form>
        </div>
        <div class="summary-box">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-number"><?php echo $totalOrders; ?></div>
                    <div class="summary-label">คำสั่งซื้อทั้งหมด</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number" style="color: #dc3545;"><?php echo $overdueCount; ?></div>
                    <div class="summary-label">เลยกำหนดส่ง</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number" style="color: #ffc107;"><?php echo $todayCount; ?></div>
                    <div class="summary-label">ส่งวันนี้</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number" style="color: #28a745;"><?php echo number_format($totalAmount, 0); ?></div>
                    <div class="summary-label">มูลค่ารวม (บาท)</div>
                </div>
            </div>
        </div>
        
        <div class="print-btn">
            <button onclick="printReport()" class="btn btn-success">🖨️ พิมพ์รายงาน</button>
        </div>
        
        <div class="card">
            <h2>รายงานกำหนดส่งสินค้า</h2>
            <p style="margin-bottom: 15px; color: #666;">
                ระหว่างวันที่ <?php echo thaiDate($dateFrom); ?> ถึง <?php echo thaiDate($dateTo); ?>
                <?php if (!empty($status)): ?>
                    | สถานะ: <?php echo $status; ?>
                <?php endif; ?>
            </p>
            
            <table>
                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>ลูกค้า</th>
                        <th>ที่อยู่/โทรศัพท์</th>
                        <th>วันที่สั่ง</th>
                        <th>กำหนดส่ง</th>
                        <th>จำนวนรายการ</th>
                        <th>ยอดรวม</th>
                        <th>สถานะ</th>
                        <th class="print-btn">จัดการ</th>
                    </tr>
                </thead>
                    <tbody>
                    <?php foreach ($orders as $order): 
                        $rowClass = '';

                        if ($order['delivery_date'] < $today && $order['status'] != 'เสร็จสิ้น') {
                            $rowClass = 'overdue';
                        } elseif ($order['delivery_date'] == $today) {
                            $rowClass = 'today';
                        }
                    ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td><?php echo $order['order_number']; ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($order['address']); ?><br>
                            <small>โทร: <?php echo $order['phone']; ?></small>
                        </td>
                        <td><?php echo thaiDate($order['order_date']); ?></td>
                        <td><?php echo thaiDate($order['delivery_date']); ?></td>
                        <td><?php echo $order['item_count']; ?> รายการ</td>
                        <td><?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><?php echo $order['status']; ?></td>
                        <td>
                            <a href="order_detail.php?id=<?php echo $order['order_id']; ?>" class="btn btn-info">
                                ดูรายละเอียด
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
            </table>
        </div>
    </div>
</body>
</html>
