<?php
require_once 'config_neon.php';
checkLogin();

$message = '';
$editMode = false;
$customer = null;

// ลบข้อมูล
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM customers WHERE customer_id = :id";
    try {
        executeQuery($conn, $sql, ['id' => $id]);
        $message = '<div class="alert success">ลบข้อมูลเรียบร้อยแล้ว</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert error">เกิดข้อผิดพลาด: ' . $e->getMessage() . '</div>';
    }
}

// แก้ไขข้อมูล
if (isset($_GET['edit'])) {
    $editMode = true;
    $id = intval($_GET['edit']);
    $customer = fetchOne($conn, "SELECT * FROM customers WHERE customer_id = :id", ['id' => $id]);
}

// บันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_code = $_POST['customer_code'];
    $customer_name = $_POST['customer_name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    
    try {
        if (isset($_POST['customer_id']) && !empty($_POST['customer_id'])) {
            // แก้ไข
            $customer_id = intval($_POST['customer_id']);
            $sql = "UPDATE customers SET customer_code=:code, customer_name=:name, address=:address, phone=:phone, email=:email WHERE customer_id=:id";
            executeQuery($conn, $sql, [
                'code' => $customer_code,
                'name' => $customer_name,
                'address' => $address,
                'phone' => $phone,
                'email' => $email,
                'id' => $customer_id
            ]);
            $message = '<div class="alert success">แก้ไขข้อมูลเรียบร้อยแล้ว</div>';
            $editMode = false;
        } else {
            // เพิ่มใหม่
            $sql = "INSERT INTO customers (customer_code, customer_name, address, phone, email) VALUES (:code, :name, :address, :phone, :email)";
            executeQuery($conn, $sql, [
                'code' => $customer_code,
                'name' => $customer_name,
                'address' => $address,
                'phone' => $phone,
                'email' => $email
            ]);
            $message = '<div class="alert success">บันทึกข้อมูลเรียบร้อยแล้ว</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert error">เกิดข้อผิดพลาด: ' . $e->getMessage() . '</div>';
    }
}

// ดึงข้อมูลทั้งหมด
$customers = fetchAll($conn, "SELECT * FROM customers ORDER BY customer_id DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลลูกค้า</title>
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
        <h1>📋 จัดการข้อมูลลูกค้า</h1>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">🏠 หน้าหลัก</a> / จัดการข้อมูลลูกค้า
        </div>
        
        <?php echo $message; ?>
        
        <div class="card">
            <h2><?php echo $editMode ? 'แก้ไขข้อมูลลูกค้า' : 'เพิ่มข้อมูลลูกค้าใหม่'; ?></h2>
            
            <form method="POST" action="">
                <?php if ($editMode): ?>
                    <input type="hidden" name="customer_id" value="<?php echo $customer['customer_id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>รหัสลูกค้า: <span style="color:red;">*</span></label>
                    <input type="text" name="customer_code" 
                           value="<?php echo $editMode ? htmlspecialchars($customer['customer_code']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label>ชื่อลูกค้า: <span style="color:red;">*</span></label>
                    <input type="text" name="customer_name" 
                           value="<?php echo $editMode ? htmlspecialchars($customer['customer_name']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label>ที่อยู่:</label>
                    <textarea name="address"><?php echo $editMode ? htmlspecialchars($customer['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>เบอร์โทรศัพท์:</label>
                    <input type="text" name="phone" 
                           value="<?php echo $editMode ? htmlspecialchars($customer['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>อีเมล:</label>
                    <input type="email" name="email" 
                           value="<?php echo $editMode ? htmlspecialchars($customer['email']) : ''; ?>">
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editMode ? '💾 บันทึกการแก้ไข' : '➕ เพิ่มข้อมูล'; ?>
                    </button>
                    <?php if ($editMode): ?>
                        <a href="customers_neon.php" class="btn btn-secondary">❌ ยกเลิก</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>รายการลูกค้าทั้งหมด</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>รหัส</th>
                        <th>ชื่อลูกค้า</th>
                        <th>ที่อยู่</th>
                        <th>โทรศัพท์</th>
                        <th>อีเมล</th>
                        <th style="width: 150px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['customer_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['address']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td>
                            <div class="actions">
                                <a href="?edit=<?php echo $row['customer_id']; ?>" class="btn btn-warning">✏️</a>
                                <a href="?delete=<?php echo $row['customer_id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('คุณต้องการลบข้อมูลนี้ใช่หรือไม่?')">🗑️</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
