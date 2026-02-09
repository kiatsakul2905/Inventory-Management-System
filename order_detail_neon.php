<?php
require_once 'config.php';
checkLogin();

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = intval($_GET['id']);

// ดึงข้อมูลคำสั่งซื้อ
$sql = "SELECT o.*, c.customer_name, c.address, c.phone 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.customer_id 
        WHERE o.order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit();
}

// ดึงรายละเอียดสินค้า
$sql = "SELECT od.*, p.product_code, p.product_name, p.unit 
        FROM order_details od 
        LEFT JOIN products p ON od.product_id = p.product_id 
        WHERE od.order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$details = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดคำสั่งซื้อ</title>
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
            max-width: 1000px;
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
        
        .order-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
        }
        
        .info-group {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
            font-size: 16px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
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
        
        .text-right {
            text-align: right;
        }
        
        .total-section {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #333;
        }
        
        .total-row {
            margin: 10px 0;
            font-size: 18px;
        }
        
        .grand-total {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .print-section {
            margin-top: 20px;
            text-align: center;
        }
        
        @media print {
            .header, .breadcrumb, .print-section {
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
        function printOrder() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>📄 รายละเอียดคำสั่งซื้อ</h1>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">🏠 หน้าหลัก</a> / 
            <a href="orders.php">บันทึกการสั่งซื้อ</a> / 
            รายละเอียด
        </div>
        
        <div class="card">
            <h2 style="text-align: center; margin-bottom: 20px;">ใบสั่งซื้อสินค้า</h2>
            
            <div class="order-header">
                <div>
                    <div class="info-group">
                        <div class="info-label">เลขที่คำสั่งซื้อ:</div>
                        <div class="info-value"><?php echo $order['order_number']; ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">วันที่สั่งซื้อ:</div>
                        <div class="info-value"><?php echo thaiDate($order['order_date']); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">กำหนดส่ง:</div>
                        <div class="info-value"><?php echo thaiDate($order['delivery_date']); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">สถานะ:</div>
                        <div class="info-value"><?php echo $order['status']; ?></div>
                    </div>
                </div>
                
                <div>
                    <div class="info-group">
                        <div class="info-label">ลูกค้า:</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">ที่อยู่:</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['address']); ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">โทรศัพท์:</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                    </div>
                </div>
            </div>
            
            <?php if ($order['notes']): ?>
            <div class="info-group" style="margin-bottom: 20px;">
                <div class="info-label">หมายเหตุ:</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
            </div>
            <?php endif; ?>
            
            <h3>รายการสินค้า</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">ลำดับ</th>
                        <th style="width: 20%;">รหัสสินค้า</th>
                        <th style="width: 30%;">ชื่อสินค้า</th>
                        <th style="width: 10%;" class="text-right">จำนวน</th>
                        <th style="width: 15%;" class="text-right">ราคา/หน่วย</th>
                        <th style="width: 15%;" class="text-right">รวม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    while ($item = $details->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="text-right"><?php echo number_format($item['quantity']); ?> <?php echo $item['unit']; ?></td>
                        <td class="text-right"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div class="total-section">
                <div class="total-row grand-total">
                    ยอดรวมทั้งสิ้น: <?php echo number_format($order['total_amount'], 2); ?> บาท
                </div>
            </div>
        </div>
        
        <div class="print-section">
            <button onclick="printOrder()" class="btn btn-success">🖨️ พิมพ์เอกสาร</button>
            <a href="orders.php" class="btn btn-secondary">← กลับ</a>
        </div>
    </div>
</body>
</html>
