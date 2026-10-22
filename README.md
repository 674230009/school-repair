# 🔧 ระบบแจ้งซ่อมภายในโรงเรียน (School Repair System)

ระบบจัดการแจ้งซ่อมอุปกรณ์และสถานที่ภายในโรงเรียน พัฒนาด้วย **PHP OOP** + **MySQL** รองรับ 3 บทบาทผู้ใช้งาน

---

## 📋 สารบัญ
- [ภาพรวมระบบ](#ภาพรวมระบบ)
- [เทคโนโลยีที่ใช้](#เทคโนโลยีที่ใช้)
- [โครงสร้างโฟลเดอร์](#โครงสร้างโฟลเดอร์)
- [การติดตั้ง](#การติดตั้ง)
- [ตั้งค่าฐานข้อมูล](#ตั้งค่าฐานข้อมูล)
- [บัญชีผู้ใช้ทดสอบ](#บัญชีผู้ใช้ทดสอบ)
- [การใช้งานตาม Role](#การใช้งานตาม-role)
- [ระบบแจ้งเตือนอีเมล](#ระบบแจ้งเตือนอีเมล)
- [Deploy บน InfinityFree](#deploy-บน-infinityfree)

---

## ภาพรวมระบบ

```
นักศึกษา/บุคลากร → แจ้งซ่อม → [pending]
                                    ↓
                    Admin มอบหมายช่าง → [accepted]
                                    ↓
                    ช่างดำเนินการซ่อม → [in_progress]
                                    ↓
                    ซ่อมเสร็จ + บันทึกผล → [completed] ✅
```

### ฟีเจอร์หลัก
- ✅ ระบบสมัครสมาชิก / เข้าสู่ระบบ
- ✅ Dashboard แยกตามบทบาท (Admin / ช่าง / นักศึกษา)
- ✅ แจ้งซ่อมพร้อมแนบรูปภาพก่อนซ่อม
- ✅ มอบหมายงานให้ช่าง + กำหนดวันเสร็จ
- ✅ ช่างอัปเดตสถานะ + บันทึกผลการซ่อม + ค่าใช้จ่าย + รูปหลังซ่อม
- ✅ ระบบความคิดเห็นในแต่ละใบแจ้งซ่อม
- ✅ แจ้งเตือนอีเมลอัตโนมัติ 3 เหตุการณ์

---

## เทคโนโลยีที่ใช้

| ส่วน | เทคโนโลยี |
|------|-----------|
| Backend | PHP 8+ (OOP, PDO, Singleton Pattern) |
| Database | MySQL / MariaDB (utf8mb4) |
| Frontend | Bootstrap 5.3 |
| Icons | Font Awesome 6.4 |
| Popup | SweetAlert2 |
| Server | XAMPP (Apache + MySQL) |

---

## โครงสร้างโฟลเดอร์

```
school_repair/
│
├── index.php                    # redirect ตาม role อัตโนมัติ
├── login.php                    # หน้าเข้าสู่ระบบ / ออกจากระบบ
├── register.php                 # หน้าสมัครสมาชิก
├── create_request.php           # ฟอร์มแจ้งซ่อม
├── view_request.php             # ดูรายละเอียด + จัดการใบแจ้งซ่อม
├── school_repair.sql            # ไฟล์สร้างฐานข้อมูล + ข้อมูลทดสอบ
│
├── pages/
│   ├── admin/
│   │   └── dashboard.php        # Dashboard สำหรับ Admin
│   ├── technician/
│   │   └── dashboard.php        # Dashboard สำหรับช่าง
│   └── student/
│       └── dashboard.php        # Dashboard สำหรับนักศึกษา/บุคลากร
│
├── config/
│   ├── database.php             # คลาส Database (Singleton Pattern)
│   └── auth.php                 # ฟังก์ชันตรวจสอบสิทธิ์ (Session)
│
├── classes/
│   └── EmailNotifier.php        # คลาสส่งอีเมลแจ้งเตือน
│
├── includes/
│   ├── header.php               # Navbar + HTML Head (ใช้ร่วมกันทุกหน้า)
│   └── footer.php               # ส่วนท้าย + Bootstrap JS
│
├── assets/
│   └── css/
│       └── style.css            # Custom CSS เพิ่มเติม
│
└── uploads/                     # โฟลเดอร์เก็บรูปภาพ (สร้างเองก่อนใช้งาน)
```

---

## การติดตั้ง

### ความต้องการของระบบ
- PHP 8.0 ขึ้นไป
- MySQL 5.7 / MariaDB 10.4 ขึ้นไป
- Apache Web Server (แนะนำ XAMPP)

### ขั้นตอน

**1. วางโปรเจกต์ใน htdocs**
```
C:\xampp\htdocs\school_repair\
```

**2. สร้างโฟลเดอร์ uploads**
```
C:\xampp\htdocs\school_repair\uploads\
```

**3. เปิด XAMPP → Start Apache + MySQL**

**4. นำเข้าฐานข้อมูล** (ดูขั้นตอนด้านล่าง)

**5. เปิดเว็บ**
```
http://localhost/school_repair/
```

---

## ตั้งค่าฐานข้อมูล

### วิธีที่ 1 — Import ไฟล์ SQL (แนะนำ)

1. เปิด `http://localhost/phpmyadmin`
2. คลิก **Import** → เลือกไฟล์ `school_repair.sql`
3. กด **Go**

### วิธีที่ 2 — ถ้ามีฐานข้อมูลเดิม (teacher → student)

รัน SQL นี้ใน phpMyAdmin:

```sql
ALTER TABLE users
MODIFY role ENUM('admin', 'student', 'technician') NOT NULL DEFAULT 'student';

UPDATE users SET role = 'student' WHERE role = 'teacher';
UPDATE users SET username = 'student1' WHERE username = 'teacher1';
UPDATE users SET username = 'student2' WHERE username = 'teacher2';
```

### ตั้งค่าการเชื่อมต่อ DB

แก้ไขค่าในไฟล์ `config/database.php`:

```php
private $host     = "localhost";          // สำหรับ XAMPP
private $db_name  = "school_repair";
private $username = "root";
private $password = "";
```

**สำหรับ InfinityFree:**
```php
private $host     = "sql207.infinityfree.com";
private $db_name  = "if0_43198980_school_repair";
private $username = "if0_43198980";
private $password = "รหัสผ่าน vPanel";
```

---

## บัญชีผู้ใช้ทดสอบ

> รหัสผ่านทุกบัญชี: **123456**

| Username | Role | หน้าที่ |
|----------|------|---------|
| `admin` | Admin | ดูทุกอย่าง + มอบหมายงาน |
| `student1` | นักศึกษา/บุคลากร | แจ้งซ่อม + ดูใบของตัวเอง |
| `student2` | นักศึกษา/บุคลากร | แจ้งซ่อม + ดูใบของตัวเอง |
| `tech1` | ช่างเทคนิค | รับงาน + อัปเดตสถานะ |
| `tech2` | ช่างเทคนิค | รับงาน + อัปเดตสถานะ |

---

## การใช้งานตาม Role

### 👨‍💼 Admin
- เห็นใบแจ้งซ่อมทุกรายการ + สถิติภาพรวม 4 การ์ด
- มอบหมายงานให้ช่าง + กำหนดวันนัดซ่อม
- สร้างใบแจ้งซ่อมแทนผู้อื่นได้
- อัปเดตสถานะการซ่อมได้

### 🔧 ช่างเทคนิค (Technician)
- เห็นงานที่ตัวเองรับผิดชอบ + งาน pending ทั้งหมด
- อัปเดตสถานะ: รับเรื่อง → กำลังซ่อม → ซ่อมเสร็จ
- บันทึกผลการซ่อม + ค่าใช้จ่าย
- แนบรูปภาพหลังซ่อม

### 👨‍🎓 นักศึกษา/บุคลากร (Student)
- กรอกแบบฟอร์มแจ้งซ่อม + แนบรูปก่อนซ่อม
- ติดตามสถานะใบแจ้งซ่อมของตัวเอง
- ดูชื่อช่างที่รับผิดชอบ + วันกำหนดเสร็จ
- เพิ่มความคิดเห็นในใบแจ้งซ่อม

---

## ระบบแจ้งเตือนอีเมล

ระบบจะส่งอีเมลอัตโนมัติใน 3 เหตุการณ์:

| เหตุการณ์ | ส่งให้ใคร | หัวข้ออีเมล |
|----------|---------|------------|
| มีใบแจ้งซ่อมใหม่ | Admin ทุกคน | `[แจ้งซ่อมใหม่] RP-XXXXXX-XXX` |
| มอบหมายงานให้ช่าง | ช่างที่รับผิดชอบ | `[งานใหม่] RP-XXXXXX-XXX` |
| อัปเดตสถานะ | ผู้แจ้งซ่อม | `[อัปเดต] สถานะล่าสุด` |

> ⚠️ **หมายเหตุ:** PHP `mail()` อาจไม่ทำงานบน Free Hosting บางเจ้า  
> ถ้าอีเมลไม่ถูกส่ง ระบบอื่นยังทำงานได้ปกติ (ไม่กระทบ flow หลัก)

---

## Deploy บน InfinityFree

1. อัปโหลดไฟล์ทั้งหมดเข้า **File Manager → htdocs/**
2. สร้างโฟลเดอร์ **uploads/** ใน htdocs
3. Import `school_repair.sql` ผ่าน **phpMyAdmin** ของ InfinityFree
4. แก้ไขค่า DB ใน `config/database.php` ให้ตรงกับ InfinityFree
5. เปิดเว็บที่ `https://schoolrepair.42web.io`

---

## ความปลอดภัย

| จุดป้องกัน | วิธีการ |
|-----------|---------|
| SQL Injection | PDO Prepared Statements ทุกจุด |
| XSS | `htmlspecialchars()` ทุกการแสดงผล |
| รหัสผ่าน | `password_hash()` bcrypt |
| การเข้าถึงหน้า | `checkLogin()` + `checkRole()` |
| File Upload | กรองสกุลไฟล์ + rename ด้วย `uniqid()` |

---

## OOP Design Patterns ที่ใช้

| Pattern | ไฟล์ | จุดประสงค์ |
|---------|------|-----------|
| **Singleton** | `config/database.php` | เชื่อมต่อ DB แค่ครั้งเดียวตลอด session |
| **Encapsulation** | `config/auth.php` | ซ่อน session logic ไว้ใน function |
| **Class** | `classes/EmailNotifier.php` | แยก logic การส่งอีเมลออกมาเป็น class |

---

*พัฒนาสำหรับวิชาการเขียนโปรแกรมเชิงวัตถุ (OOP)*
