<?php
require_once 'config_neon.php';
checkLogin();

// ดึงสถิติต่างๆ
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM customers) as total_customers,
        (SELECT COUNT(*) FROM products) as total_products,
        (SELECT COUNT(*) FROM orders) as total_orders,
        (SELECT COUNT(*) FROM orders WHERE status = 'รอดำเนินการ') as pending_orders
";
$stats = fetchOne($conn, $statsQuery);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก - ระบบบริหารจัดการสินค้าคงคลัง</title>
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
            margin-bottom: 5px;
        }
        
        .user-info {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .neon-badge {
            background: #00e699;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .welcome h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome p {
            color: #666;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .menu-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .menu-section h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 18px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .menu-item {
            display: block;
            padding: 12px 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            border-left: 3px solid #667eea;
        }
        
        .menu-item:hover {
            background: #667eea;
            color: white;
            transform: translateX(5px);
        }
        
        .menu-item::before {
            content: "→ ";
            margin-right: 5px;
        }
        
        .logout-section {
            background: #fff3e0;
            border-left-color: #ff9800;
        }
        
        .logout-section h3 {
            color: #ff9800;
            border-bottom-color: #ff9800;
        }
        
        .logout-item {
            background: #ffe0b2;
            border-left-color: #ff9800;
            color: #e65100;
        }
        
        .logout-item:hover {
            background: #ff9800;
            color: white;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
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
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏢 ระบบบริหารจัดการสินค้าคงคลัง <span class="neon-badge">⚡ Neon</span></h1>
        <div class="user-info">
            ผู้ใช้งาน: <?php echo htmlspecialchars($_SESSION['full_name']); ?> 
            (<?php echo htmlspecialchars($_SESSION['username']); ?>)
        </div>
    </div>
    
    <div class="container">
        <div class="welcome">
            <h2>ยินดีต้อนรับเข้าสู่ระบบ</h2>
            <p>กรุณาเลือกเมนูที่ต้องการใช้งาน</p>
        </div>
        
        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_customers']; ?></div>
                <div class="stat-label">ลูกค้าทั้งหมด</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_products']; ?></div>
                <div class="stat-label">รายการสินค้า</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['pending_orders']; ?></div>
                <div class="stat-label">รอดำเนินการ</div>
            </div>
        </div>
        
        <div class="menu-grid">
            <div class="menu-section">
                <h3>1. ฐานข้อมูลอ้างอิง</h3>
                <a href="customers_neon.php" class="menu-item">1.1 บันทึก/แก้ไข ข้อมูลลูกค้า</a>
                <a href="products_neon.php" class="menu-item">1.2 บันทึก/แก้ไข ข้อมูลสินค้า</a>
            </div>
            
            <div class="menu-section">
                <h3>2. การทำงานประจำวัน</h3>
                <a href="orders_neon.php" class="menu-item">2.1 บันทึก/แก้ไข การสั่งซื้อสินค้า</a>
                <a href="process_orders_neon.php" class="menu-item">2.2 การประมวลผลข้อมูลการสั่งสินค้า</a>
            </div>
            
            <div class="menu-section">
                <h3>3. รายงาน</h3>
                <a href="delivery_report_neon.php" class="menu-item">3.1 รายงานกำหนดส่งสินค้า</a>
            </div>
            
            <div class="menu-section logout-section">
                <h3>4. ออกจากระบบ</h3>
                <a href="logout_neon.php" class="menu-item logout-item" 
                   onclick="return confirm('คุณต้องการออกจากระบบใช่หรือไม่?')">
                    4.1 ออกจากระบบโปรแกรม
                </a>
            </div>
        </div>
    </div>
</body>
</html>
