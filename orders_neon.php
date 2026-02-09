<?php
require_once 'config_neon.php';
checkLogin();

$message = '';

// ดึงข้อมูลลูกค้า
$customers = $conn->query("SELECT * FROM customers ORDER BY customer_name")
                  ->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลสินค้า
$products = $conn->query("SELECT * FROM products ORDER BY product_name")
                 ->fetchAll(PDO::FETCH_ASSOC);

// บันทึกคำสั่งซื้อ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_order'])) {

    $customer_id = intval($_POST['customer_id']);
    $order_date = $_POST['order_date'];
    $delivery_date = $_POST['delivery_date'] ?: null;
    $notes = $_POST['notes'];

    $order_number = generateOrderNumber($conn);

    $total_amount = 0;
    if (!empty($_POST['product_id'])) {
        foreach ($_POST['product_id'] as $k => $pid) {
            $qty = intval($_POST['quantity'][$k]);
            $price = floatval($_POST['unit_price'][$k]);
            $total_amount += ($qty * $price);
        }
    }

    try {
        $conn->beginTransaction();

        // insert order
        $stmt = $conn->prepare("
            INSERT INTO orders
            (order_number, customer_id, order_date, delivery_date, total_amount, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $order_number,
            $customer_id,
            $order_date,
            $delivery_date,
            $total_amount,
            $notes
        ]);

        $order_id = $conn->lastInsertId();

        // insert details
        if (!empty($_POST['product_id'])) {

            $stmtDetail = $conn->prepare("
                INSERT INTO order_details
                (order_id, product_id, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($_POST['product_id'] as $k => $pid) {

                if (!empty($pid)) {
                    $qty = intval($_POST['quantity'][$k]);
                    $price = floatval($_POST['unit_price'][$k]);
                    $total = $qty * $price;

                    $stmtDetail->execute([
                        $order_id,
                        $pid,
                        $qty,
                        $price,
                        $total
                    ]);
                }
            }
        }

        $conn->commit();

        $message = '<div class="alert success">บันทึกคำสั่งซื้อเลขที่ '
            . $order_number . ' เรียบร้อยแล้ว</div>';

    } catch (Exception $e) {
        $conn->rollBack();
        $message = '<div class="alert error">Error: ' . $e->getMessage() . '</div>';
    }
}

// โหลด orders
$sql = "
SELECT o.*, c.customer_name
FROM orders o
LEFT JOIN customers c ON o.customer_id = c.customer_id
ORDER BY o.order_id DESC
";

$orders = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกการสั่งซื้อสินค้า</title>
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .order-items {
            margin: 20px 0;
        }
        
        .order-items table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .order-items th,
        .order-items td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        
        .order-items th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .order-items input,
        .order-items select {
            padding: 8px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
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
        }
        
        .total-section {
            text-align: right;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
        }
        
        .status-pending {
            color: #ffc107;
        }
        
        .status-completed {
            color: #28a745;
        }
    </style>
    <script>
        function addRow() {
            const table = document.getElementById('orderItemsTable');
            const row = table.insertRow(-1);
            
            row.innerHTML = `
                <td>
                    <select name="product_id[]" onchange="updatePrice(this)" class="product-select">
                        <option value="">-- เลือกสินค้า --</option>
                        <?php 
                        foreach ($products as $p): 
                        ?>
                        <option value="<?php echo $p['product_id']; ?>" 
                                data-price="<?php echo $p['unit_price']; ?>"
                                data-unit="<?php echo $p['unit']; ?>">
                            <?php echo $p['product_code'] . ' - ' . $p['product_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" name="quantity[]" value="1" min="1" onchange="calculateTotal()" class="quantity-input"></td>
                <td><input type="number" step="0.01" name="unit_price[]" value="0" readonly class="unit-price"></td>
                <td class="item-total">0.00</td>
                <td><button type="button" onclick="removeRow(this)" class="btn btn-danger">ลบ</button></td>
            `;
        }
        
        function removeRow(btn) {
            const row = btn.closest('tr');
            row.remove();
            calculateTotal();
        }
        
        function updatePrice(select) {
            const row = select.closest('tr');
            const option = select.options[select.selectedIndex];
            const price = option.getAttribute('data-price') || 0;
            row.querySelector('.unit-price').value = price;
            calculateTotal();
        }
        
        function calculateTotal() {
            let grandTotal = 0;
            const rows = document.querySelectorAll('#orderItemsTable tr');
            
            rows.forEach((row, index) => {
                if (index > 0) { // ข้าม header row
                    const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
                    const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
                    const itemTotal = quantity * unitPrice;
                    
                    row.querySelector('.item-total').textContent = itemTotal.toFixed(2);
                    grandTotal += itemTotal;
                }
            });
            
            document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>🛒 บันทึกการสั่งซื้อสินค้า</h1>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">🏠 หน้าหลัก</a> / บันทึกการสั่งซื้อสินค้า
        </div>
        
        <?php echo $message; ?>
        
        <div class="card">
            <h2>สร้างคำสั่งซื้อใหม่</h2>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>ลูกค้า: <span style="color:red;">*</span></label>
                        <select name="customer_id" required>
                            <option value="">-- เลือกลูกค้า --</option>
                            <?php 
                            foreach ($customers as $c): 
                            ?>
                            <option value="<?php echo $c['customer_id']; ?>">
                                <?php echo $c['customer_code'] . ' - ' . $c['customer_name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>วันที่สั่งซื้อ: <span style="color:red;">*</span></label>
                        <input type="date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>กำหนดส่ง:</label>
                        <input type="date" name="delivery_date">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>หมายเหตุ:</label>
                    <textarea name="notes" rows="3"></textarea>
                </div>
                
                <div class="order-items">
                    <h3>รายการสินค้า</h3>
                    <button type="button" onclick="addRow()" class="btn btn-success">➕ เพิ่มรายการ</button>
                    
                    <table id="orderItemsTable">
                        <thead>
                            <tr>
                                <th style="width: 40%;">สินค้า</th>
                                <th style="width: 15%;">จำนวน</th>
                                <th style="width: 15%;">ราคา/หน่วย</th>
                                <th style="width: 15%;">รวม</th>
                                <th style="width: 15%;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="product_id[]" onchange="updatePrice(this)" class="product-select">
                                        <option value="">-- เลือกสินค้า --</option>
                                        <?php 
                                        foreach ($products as $p):
                                        ?>
                                        <option value="<?php echo $p['product_id']; ?>" 
                                                data-price="<?php echo $p['unit_price']; ?>">
                                            <?php echo $p['product_code'] . ' - ' . $p['product_name']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="quantity[]" value="1" min="1" onchange="calculateTotal()" class="quantity-input"></td>
                                <td><input type="number" step="0.01" name="unit_price[]" value="0" readonly class="unit-price"></td>
                                <td class="item-total">0.00</td>
                                <td><button type="button" onclick="removeRow(this)" class="btn btn-danger">ลบ</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="total-section">
                    ยอดรวมทั้งสิ้น: <span id="grandTotal">0.00</span> บาท
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" name="save_order" class="btn btn-primary">💾 บันทึกคำสั่งซื้อ</button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>รายการคำสั่งซื้อทั้งหมด</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>ลูกค้า</th>
                        <th>วันที่สั่ง</th>
                        <th>กำหนดส่ง</th>
                        <th>ยอดรวม</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
<?php while ($order = $orders->fetch(PDO::FETCH_ASSOC)): ?>
<tr>
    <td><?= $order['order_number'] ?></td>
    <td><?= htmlspecialchars($order['customer_name']) ?></td>
    <td><?= thaiDate($order['order_date']) ?></td>
    <td><?= thaiDate($order['delivery_date']) ?></td>
    <td><?= number_format($order['total_amount'], 2) ?></td>
    <td class="<?= $order['status']=='รอดำเนินการ'?'status-pending':'status-completed'; ?>">
        <?= $order['status'] ?>
    </td>
    <td>
        <a href="order_detail.php?id=<?= $order['order_id']; ?>" class="btn btn-info">
            📄 ดูรายละเอียด
        </a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
            </table>
        </div>
    </div>
</body>
</html>
