<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ออกจากระบบ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Sarabun', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .logout-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 400px;
        }
        
        .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .neon-badge {
            background: #00e699;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
    <script>
        setTimeout(function() {
            window.location.href = 'login_neon.php';
        }, 3000);
    </script>
</head>
<body>
    <div class="logout-container">
        <span class="neon-badge">⚡ Powered by Neon</span>
        <div class="icon">👋</div>
        <h1>ออกจากระบบเรียบร้อยแล้ว</h1>
        <p>ขอบคุณที่ใช้บริการระบบบริหารจัดการสินค้าคงคลัง</p>
        <p style="font-size: 14px; color: #999;">กำลังนำท่านกลับไปหน้าเข้าสู่ระบบ...</p>
        <a href="login_neon.php" class="btn">กลับไปหน้าเข้าสู่ระบบ</a>
    </div>
</body>
</html>
