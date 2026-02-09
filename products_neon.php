<?php
require_once 'config_neon.php';
checkLogin();

$message = '';
$editMode = false;
$product = null;

// ลบข้อมูล
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = '<div class="alert success">ลบข้อมูลเรียบร้อยแล้ว</div>';
    } else {
        $message = '<div class="alert error">เกิดข้อผิดพลาด: ' . $conn->error . '</div>';
    }
}

// แก้ไขข้อมูล
if (isset($_GET['edit'])) {
    $editMode = true;
    $id = intval($_GET['edit']);
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
}

// บันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_code = $_POST['product_code'];
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $unit_price = floatval($_POST['unit_price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $unit = $_POST['unit'];
    
    if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
        // แก้ไข
        $product_id = intval($_POST['product_id']);
        $sql = "UPDATE products SET product_code=?, product_name=?, description=?, unit_price=?, stock_quantity=?, unit=? WHERE product_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdiis", $product_code, $product_name, $description, $unit_price, $stock_quantity, $unit, $product_id);
        if ($stmt->execute()) {
            $message = '<div class="alert success">แก้ไขข้อมูลเรียบร้อยแล้ว</div>';
            $editMode = false;
        } else {
            $message = '<div class="alert error">เกิดข้อผิดพลาด: ' . $conn->error . '</div>';
        }
    } else {
        // เพิ่มใหม่
        $sql = "INSERT INTO products (product_code, product_name, description, unit_price, stock_quantity, unit) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdis", $product_code, $product_name, $description, $unit_price, $stock_quantity, $unit);
        if ($stmt->execute()) {
            $message = '<div class="alert success">บันทึกข้อมูลเรียบร้อยแล้ว</div>';
        } else {
            $message = '<div class="alert error">เกิดข้อผิดพลาด: ' . $conn->error . '</div>';
        }
    }
}

// ดึงข้อมูลทั้งหมด
$sql = "SELECT * FROM products ORDER BY product_id DESC";
$products = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลสินค้า</title>
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
            max-width: 1200px;
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
        
        .breadcrumb a:hover {
            text-decoration: underline;
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
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        textarea {
            min-height: 80px;
            resize: vertical;
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
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-warning:hover {
            background: #e0a800;
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
            font-weight: bold;
            color: #333;
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
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .stock-low {
            color: #dc3545;
            font-weight: bold;
        }
        
        .stock-ok {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 จัดการข้อมูลสินค้า</h1>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">🏠 หน้าหลัก</a> / จัดการข้อมูลสินค้า
        </div>
        
        <?php echo $message; ?>
        
        <div class="card">
            <h2><?php echo $editMode ? 'แก้ไขข้อมูลสินค้า' : 'เพิ่มข้อมูลสินค้าใหม่'; ?></h2>
            
            <form method="POST" action="">
                <?php if ($editMode): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>รหัสสินค้า: <span style="color:red;">*</span></label>
                        <input type="text" name="product_code" 
                               value="<?php echo $editMode ? htmlspecialchars($product['product_code']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label>ชื่อสินค้า: <span style="color:red;">*</span></label>
                        <input type="text" name="product_name" 
                               value="<?php echo $editMode ? htmlspecialchars($product['product_name']) : ''; ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label>รายละเอียด:</label>
                    <textarea name="description"><?php echo $editMode ? htmlspecialchars($product['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ราคาต่อหน่วย (บาท): <span style="color:red;">*</span></label>
                        <input type="number" step="0.01" name="unit_price" 
                               value="<?php echo $editMode ? $product['unit_price'] : '0'; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label>จำนวนคงคลัง: <span style="color:red;">*</span></label>
                        <input type="number" name="stock_quantity" 
                               value="<?php echo $editMode ? $product['stock_quantity'] : '0'; ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>หน่วยนับ: <span style="color:red;">*</span></label>
                    <select name="unit" required>
                        <option value="ชิ้น" <?php echo ($editMode && $product['unit']=='ชิ้น')?'selected':''; ?>>ชิ้น</option>
                        <option value="กล่อง" <?php echo ($editMode && $product['unit']=='กล่อง')?'selected':''; ?>>กล่อง</option>
                        <option value="แพ็ค" <?php echo ($editMode && $product['unit']=='แพ็ค')?'selected':''; ?>>แพ็ค</option>
                        <option value="ถุง" <?php echo ($editMode && $product['unit']=='ถุง')?'selected':''; ?>>ถุง</option>
                        <option value="ลัง" <?php echo ($editMode && $product['unit']=='ลัง')?'selected':''; ?>>ลัง</option>
                        <option value="กก." <?php echo ($editMode && $product['unit']=='กก.')?'selected':''; ?>>กก.</option>
                        <option value="ลิตร" <?php echo ($editMode && $product['unit']=='ลิตร')?'selected':''; ?>>ลิตร</option>
                    </select>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editMode ? '💾 บันทึกการแก้ไข' : '➕ เพิ่มข้อมูล'; ?>
                    </button>
                    <?php if ($editMode): ?>
                        <a href="products.php" class="btn btn-secondary">❌ ยกเลิก</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>รายการสินค้าทั้งหมด</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>รหัส</th>
                        <th>ชื่อสินค้า</th>
                        <th>รายละเอียด</th>
                        <th>ราคา/หน่วย</th>
                        <th>คงคลัง</th>
                        <th>หน่วย</th>
                        <th style="width: 150px;">จัดการ</th>
                    </tr>
                </thead>
              <tbody>
                <?php while ($row = $products->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['product_code']); ?></td>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td><?php echo number_format($row['unit_price'], 2); ?></td>
                    <td class="<?php echo $row['stock_quantity'] < 10 ? 'stock-low' : 'stock-ok'; ?>">
                        <?php echo number_format($row['stock_quantity']); ?>
                        <?php if ($row['stock_quantity'] < 10): ?>
                            ⚠️
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['unit']); ?></td>
                    <td>
                        <div class="actions">
                            <a href="?edit=<?php echo $row['product_id']; ?>" class="btn btn-warning">✏️ แก้ไข</a>
                            <a href="?delete=<?php echo $row['product_id']; ?>" 
                            class="btn btn-danger"
                            onclick="return confirm('คุณต้องการลบข้อมูลนี้ใช่หรือไม่?')">🗑️ ลบ</a>
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
