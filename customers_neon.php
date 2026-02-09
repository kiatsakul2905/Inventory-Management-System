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
    <link rel="stylesheet" href="style.css">
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
