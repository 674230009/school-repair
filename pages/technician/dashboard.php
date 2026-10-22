<?php
// pages/technician/dashboard.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

checkRole(['technician']);
$user = currentUser();

try {
    $pdo = Database::getInstance();

    // สถิติงานของช่างคนนี้
    $stmtMy = $pdo->prepare("SELECT COUNT(*) FROM repair_requests WHERE assigned_to = :uid");
    $stmtMy->execute(['uid' => $user['id']]);

    $stmtPending = $pdo->query("SELECT COUNT(*) FROM repair_requests WHERE status = 'pending'");

    $stmtProgress = $pdo->prepare("SELECT COUNT(*) FROM repair_requests WHERE assigned_to = :uid AND status IN ('accepted','in_progress')");
    $stmtProgress->execute(['uid' => $user['id']]);

    $stmtDone = $pdo->prepare("SELECT COUNT(*) FROM repair_requests WHERE assigned_to = :uid AND status = 'completed'");
    $stmtDone->execute(['uid' => $user['id']]);

    $stats = [
        'my_jobs'     => $stmtMy->fetchColumn(),
        'pending'     => $stmtPending->fetchColumn(),
        'in_progress' => $stmtProgress->fetchColumn(),
        'completed'   => $stmtDone->fetchColumn(),
    ];

    // งานที่รับผิดชอบ + งาน pending ทั้งหมดที่รอรับ
    $stmt = $pdo->prepare("
        SELECT r.*, u.fullname AS reporter_name, t.fullname AS tech_name
        FROM repair_requests r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN users t ON r.assigned_to = t.id
        WHERE r.assigned_to = :uid OR r.status = 'pending'
        ORDER BY r.urgency DESC, r.created_at DESC
    ");
    $stmt->execute(['uid' => $user['id']]);
    $requests = $stmt->fetchAll();
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    $stats = ['my_jobs' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0];
    $requests = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fa-solid fa-helmet-safety me-2"></i> หน้าจอช่างเทคนิค</h2>
    <span class="text-muted">สวัสดี, <?= htmlspecialchars($user['fullname']) ?></span>
</div>

<!-- สถิติ -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div><h6 class="mb-1">งานทั้งหมดของฉัน</h6><h3 class="mb-0 fw-bold"><?= $stats['my_jobs'] ?></h3></div>
                <i class="fa-solid fa-wrench fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-warning text-dark p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div><h6 class="mb-1">รอรับเรื่อง (ทั้งระบบ)</h6><h3 class="mb-0 fw-bold"><?= $stats['pending'] ?></h3></div>
                <i class="fa-solid fa-clock fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div><h6 class="mb-1">กำลังดำเนินการ</h6><h3 class="mb-0 fw-bold"><?= $stats['in_progress'] ?></h3></div>
                <i class="fa-solid fa-spinner fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div><h6 class="mb-1">ซ่อมเสร็จแล้ว</h6><h3 class="mb-0 fw-bold"><?= $stats['completed'] ?></h3></div>
                <i class="fa-solid fa-circle-check fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
</div>

<!-- ตารางงาน -->
<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-secondary"><i class="fa-solid fa-list me-2"></i> รายการงานซ่อม</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>รหัสแจ้งซ่อม</th>
                        <th>ผู้แจ้ง</th>
                        <th>สถานที่</th>
                        <th>ประเภทปัญหา</th>
                        <th>ความเร่งด่วน</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $req): ?>
                            <?php
                            // ไฮไลต์แถวงานที่เป็นของช่างคนนี้
                            $rowClass = ($req['assigned_to'] == $user['id']) ? '' : 'table-light';
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td>
                                    <strong><?= htmlspecialchars($req['request_no']) ?></strong>
                                    <?php if ($req['assigned_to'] == $user['id']): ?>
                                        <span class="badge bg-primary ms-1">งานของฉัน</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($req['reporter_name']) ?></td>
                                <td><?= htmlspecialchars($req['building'] . ' (' . $req['room'] . ')') ?></td>
                                <td>
                                    <?php
                                    $problemTypeBadges = [
                                        'electrical' => '<span class="badge bg-warning text-dark"><i class="fa-solid fa-bolt me-1"></i>ระบบไฟฟ้า</span>',
                                        'furniture'  => '<span class="badge bg-secondary"><i class="fa-solid fa-chair me-1"></i>ครุภัณฑ์</span>',
                                        'computer'   => '<span class="badge bg-info text-dark"><i class="fa-solid fa-desktop me-1"></i>คอมพิวเตอร์</span>',
                                        'aircon'     => '<span class="badge bg-primary"><i class="fa-solid fa-snowflake me-1"></i>เครื่องปรับอากาศ</span>',
                                        'other'      => '<span class="badge bg-dark"><i class="fa-solid fa-screwdriver-wrench me-1"></i>อื่นๆ</span>',
                                    ];
                                    echo $problemTypeBadges[$req['problem_type']] ?? htmlspecialchars($req['problem_type']);
                                    ?>
                                </td>
                                <td>
                                    <?php if ($req['urgency'] === 'high'): ?>
                                        <span class="badge bg-danger">ด่วนมาก</span>
                                    <?php elseif ($req['urgency'] === 'medium'): ?>
                                        <span class="badge bg-warning text-dark">ปานกลาง</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">ปกติ</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'pending'     => '<span class="badge bg-warning text-dark">รอรับเรื่อง</span>',
                                        'accepted'    => '<span class="badge bg-info">รับเรื่องแล้ว</span>',
                                        'in_progress' => '<span class="badge bg-primary">กำลังดำเนินการ</span>',
                                        'completed'   => '<span class="badge bg-success">เสร็จสมบูรณ์</span>',
                                    ];
                                    echo $statusBadges[$req['status']] ?? $req['status'];
                                    ?>
                                </td>
                                <td>
                                    <a href="../../view_request.php?id=<?= $req['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fa-solid fa-eye"></i> รายละเอียด
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">ยังไม่มีงานในขณะนี้</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
