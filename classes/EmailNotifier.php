<?php
// classes/EmailNotifier.php

class EmailNotifier {

    private string $fromEmail;
    private string $fromName;
    private string $siteUrl;

    public function __construct(
        string $fromEmail = 'noreply@schoolrepair.42web.io',
        string $fromName  = 'ระบบแจ้งซ่อมโรงเรียน',
        string $siteUrl   = 'https://schoolrepair.42web.io'
    ) {
        $this->fromEmail = $fromEmail;
        $this->fromName  = $fromName;
        $this->siteUrl   = $siteUrl;
    }

    // ==========================================
    // Private: ส่งอีเมลจริง
    // ==========================================
    private function send(string $to, string $subject, string $body): bool {
        if (empty(trim($to))) return false;

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        return @mail($to, $encodedSubject, $body, $headers);
    }

    // ==========================================
    // Public: แจ้ง Admin เมื่อมีใบแจ้งซ่อมใหม่
    // ==========================================
    public function notifyNewRequest(array $adminEmails, array $req, string $reporterName): void {
        $subject = "[แจ้งซ่อมใหม่] {$req['request_no']} — {$req['building']}";
        $body    = $this->templateNewRequest($req, $reporterName);
        foreach ($adminEmails as $email) {
            $this->send($email, $subject, $body);
        }
    }

    // ==========================================
    // Public: แจ้งช่างเมื่อได้รับมอบหมายงาน
    // ==========================================
    public function notifyAssigned(string $techEmail, string $techName, array $req): void {
        $subject = "[งานใหม่] {$req['request_no']} — รอการดำเนินการ";
        $body    = $this->templateAssigned($req, $techName);
        $this->send($techEmail, $subject, $body);
    }

    // ==========================================
    // Public: แจ้งผู้แจ้งซ่อมเมื่อสถานะเปลี่ยน
    // ==========================================
    public function notifyStatusUpdate(string $reporterEmail, string $reporterName, array $req, string $techName = ''): void {
        $statusLabel = [
            'accepted'    => 'รับเรื่องแล้ว',
            'in_progress' => 'กำลังดำเนินการซ่อม',
            'completed'   => 'ซ่อมเสร็จแล้ว ✅',
        ][$req['status']] ?? $req['status'];

        $subject = "[อัปเดต] {$req['request_no']} — {$statusLabel}";
        $body    = $this->templateStatusUpdate($req, $reporterName, $techName, $statusLabel);
        $this->send($reporterEmail, $subject, $body);
    }

    // ==========================================
    // Templates HTML
    // ==========================================

    private function htmlWrapper(string $title, string $badgeColor, string $content): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="th">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:sans-serif;">
<div style="max-width:600px;margin:30px auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
  <div style="background:{$badgeColor};padding:20px 25px;">
    <h2 style="margin:0;color:#fff;font-size:18px;">🔧 ระบบแจ้งซ่อมโรงเรียน</h2>
    <p style="margin:5px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">{$title}</p>
  </div>
  <div style="padding:25px;">
    {$content}
  </div>
  <div style="background:#f8f9fa;padding:12px 25px;text-align:center;border-top:1px solid #dee2e6;">
    <small style="color:#6c757d;">อีเมลนี้ส่งโดยอัตโนมัติ กรุณาอย่าตอบกลับ</small>
  </div>
</div>
</body>
</html>
HTML;
    }

    private function infoRow(string $label, string $value): string {
        return <<<HTML
<tr>
  <td style="padding:8px 10px;border-bottom:1px solid #f0f0f0;color:#555;width:40%;font-size:14px;"><strong>{$label}</strong></td>
  <td style="padding:8px 10px;border-bottom:1px solid #f0f0f0;color:#333;font-size:14px;">{$value}</td>
</tr>
HTML;
    }

    private function templateNewRequest(array $req, string $reporterName): string {
        $urgencyLabel = ['low' => 'ปกติ 🟢', 'medium' => 'ปานกลาง 🟡', 'high' => 'ด่วนมาก 🔴'][$req['urgency']] ?? $req['urgency'];
        $problemLabel = [
            'electrical' => 'ระบบไฟฟ้า',
            'furniture'  => 'ครุภัณฑ์ / เฟอร์นิเจอร์',
            'computer'   => 'ระบบคอมพิวเตอร์ / เครือข่าย',
            'aircon'     => 'เครื่องปรับอากาศ',
            'other'      => 'อื่นๆ',
        ][$req['problem_type']] ?? $req['problem_type'];

        $rows = $this->infoRow('เลขที่ใบแจ้งซ่อม', htmlspecialchars($req['request_no']))
              . $this->infoRow('ผู้แจ้ง', htmlspecialchars($reporterName))
              . $this->infoRow('สถานที่', htmlspecialchars($req['building'] . ' ห้อง ' . $req['room']))
              . $this->infoRow('ประเภทปัญหา', $problemLabel)
              . $this->infoRow('ความเร่งด่วน', $urgencyLabel)
              . $this->infoRow('รายละเอียด', htmlspecialchars($req['description']));

        $link = $this->siteUrl . '/view_request.php?id=' . ($req['id'] ?? '');
        $content = <<<HTML
<p style="color:#333;margin-bottom:15px;">มีใบแจ้งซ่อมใหม่เข้าระบบ กรุณาตรวจสอบและมอบหมายงานให้ช่างครับ/ค่ะ</p>
<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">{$rows}</table>
<div style="text-align:center;">
  <a href="{$link}" style="background:#0d6efd;color:#fff;padding:10px 25px;border-radius:6px;text-decoration:none;font-size:14px;">ดูรายละเอียดใบแจ้งซ่อม</a>
</div>
HTML;
        return $this->htmlWrapper('มีใบแจ้งซ่อมใหม่รอการมอบหมาย', '#0d6efd', $content);
    }

    private function templateAssigned(array $req, string $techName): string {
        $dueDate = !empty($req['due_date']) ? date('d/m/Y', strtotime($req['due_date'])) : 'ยังไม่กำหนด';
        $problemLabel = [
            'electrical' => 'ระบบไฟฟ้า',
            'furniture'  => 'ครุภัณฑ์ / เฟอร์นิเจอร์',
            'computer'   => 'ระบบคอมพิวเตอร์ / เครือข่าย',
            'aircon'     => 'เครื่องปรับอากาศ',
            'other'      => 'อื่นๆ',
        ][$req['problem_type']] ?? $req['problem_type'];

        $rows = $this->infoRow('เลขที่ใบแจ้งซ่อม', htmlspecialchars($req['request_no']))
              . $this->infoRow('สถานที่', htmlspecialchars($req['building'] . ' ห้อง ' . $req['room']))
              . $this->infoRow('ประเภทปัญหา', $problemLabel)
              . $this->infoRow('รายละเอียด', htmlspecialchars($req['description']))
              . $this->infoRow('กำหนดเสร็จ', $dueDate);

        $link = $this->siteUrl . '/view_request.php?id=' . ($req['id'] ?? '');
        $content = <<<HTML
<p style="color:#333;margin-bottom:15px;">สวัสดีคุณ <strong>{$techName}</strong> คุณได้รับมอบหมายงานซ่อมใหม่ กรุณาดำเนินการตามกำหนด</p>
<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">{$rows}</table>
<div style="text-align:center;">
  <a href="{$link}" style="background:#198754;color:#fff;padding:10px 25px;border-radius:6px;text-decoration:none;font-size:14px;">ดูรายละเอียดงาน</a>
</div>
HTML;
        return $this->htmlWrapper('คุณได้รับมอบหมายงานซ่อมใหม่', '#198754', $content);
    }

    private function templateStatusUpdate(array $req, string $reporterName, string $techName, string $statusLabel): string {
        $statusColor = [
            'accepted'    => '#0dcaf0',
            'in_progress' => '#0d6efd',
            'completed'   => '#198754',
        ][$req['status']] ?? '#6c757d';

        $rows = $this->infoRow('เลขที่ใบแจ้งซ่อม', htmlspecialchars($req['request_no']))
              . $this->infoRow('สถานที่', htmlspecialchars($req['building'] . ' ห้อง ' . $req['room']))
              . $this->infoRow('สถานะปัจจุบัน', "<span style='background:{$statusColor};color:#fff;padding:3px 10px;border-radius:4px;font-size:13px;'>{$statusLabel}</span>")
              . $this->infoRow('ช่างผู้รับผิดชอบ', !empty($techName) ? htmlspecialchars($techName) : 'ยังไม่กำหนด');

        if ($req['status'] === 'completed' && !empty($req['result'])) {
            $rows .= $this->infoRow('ผลการซ่อม', htmlspecialchars($req['result']));
        }
        if ($req['status'] === 'completed' && !empty($req['cost'])) {
            $rows .= $this->infoRow('ค่าใช้จ่าย', number_format((float)$req['cost'], 2) . ' บาท');
        }

        $link = $this->siteUrl . '/view_request.php?id=' . ($req['id'] ?? '');
        $content = <<<HTML
<p style="color:#333;margin-bottom:15px;">สวัสดีคุณ <strong>{$reporterName}</strong> ใบแจ้งซ่อมของคุณมีการอัปเดตสถานะใหม่</p>
<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">{$rows}</table>
<div style="text-align:center;">
  <a href="{$link}" style="background:{$statusColor};color:#fff;padding:10px 25px;border-radius:6px;text-decoration:none;font-size:14px;">ติดตามความคืบหน้า</a>
</div>
HTML;
        return $this->htmlWrapper("อัปเดตสถานะใบแจ้งซ่อม: {$statusLabel}", $statusColor, $content);
    }
}
?>
