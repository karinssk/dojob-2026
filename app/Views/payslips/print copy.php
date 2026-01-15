<!DOCTYPE html>
<html>
<head>
  <title>Payslip #<?= $payslip['id'] ?></title>
  <meta charset="UTF-8">
  <style>
    @page {
      size: A4 portrait;
      margin: 20mm;
    }
    
    body { 
      font-family: Arial, sans-serif; 
      margin: 0; 
      padding: 0; 
      color: black;
      font-size: 12px;
      line-height: 1.4;
      background: white;
    }
    
    .payslip-container {
      width: 100%;
      background: white;
      margin: 0 auto;
    }
    
    .header-section {
      width: 100%;
      margin-bottom: 20px;
      border-bottom: 2px solid black;
      padding-bottom: 15px;
    }
    
    .header-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 15px;
    }
    
    .company-info {
      flex: 1;
    }
    
    .company-name {
      font-size: 20px;
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .logo-section {
      flex: 0 0 80px;
      text-align: center;
    }
    
    .logo-placeholder {
      width: 70px;
      height: 70px;
      border: 2px solid black;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 14px;
    }
    
    .payslip-info {
      flex: 1;
      text-align: right;
    }
    
    .payslip-title {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .employee-details {
      display: flex;
      justify-content: space-between;
      margin-top: 10px;
      font-size: 11px;
    }
    
    .employee-left, .employee-right {
      flex: 1;
    }
    
    .employee-right {
      text-align: right;
    }
    
    .main-content {
      margin: 20px 0;
    }
    
    .earnings-section, .deductions-section {
      margin-bottom: 20px;
    }
    
    .section-title {
      background: black;
      color: white;
      padding: 8px 15px;
      font-weight: bold;
      font-size: 14px;
      text-align: center;
      margin-bottom: 0;
    }
    
    .section-content {
      border: 2px solid black;
      border-top: none;
      padding: 15px;
    }
    
    .item-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 5px 0;
      border-bottom: 1px dotted #ccc;
      margin-bottom: 5px;
    }
    
    .item-row:last-child {
      border-bottom: none;
      margin-bottom: 0;
    }
    
    .item-label {
      flex: 1;
      font-size: 12px;
    }
    
    .item-value {
      font-weight: bold;
      font-size: 12px;
      text-align: right;
      min-width: 100px;
    }
    
    .thai-label {
      color: #666;
      font-size: 10px;
      display: block;
      margin-top: 2px;
    }
    
    .total-row {
      border-top: 2px solid black;
      padding: 10px;
      margin: 15px -15px -15px -15px;
      font-weight: bold;
      background: #f5f5f5;
    }
    
    .summary-section {
      display: flex;
      justify-content: space-between;
      margin: 20px 0;
      gap: 20px;
    }
    
    .summary-left, .summary-right {
      flex: 1;
      padding: 15px;
      border: 2px solid black;
    }
    
    .summary-title {
      font-weight: bold;
      margin-bottom: 10px;
      text-align: center;
      border-bottom: 1px solid black;
      padding-bottom: 5px;
      font-size: 13px;
    }
    
    .net-pay-section {
      text-align: center;
      padding: 20px;
      margin: 20px 0;
      border: 3px solid black;
      background: #f9f9f9;
    }
    
    .net-pay-label {
      font-size: 16px;
      font-weight: bold;
      margin-bottom: 10px;
    }
    
    .net-pay-amount {
      font-size: 28px;
      font-weight: bold;
    }
    
    .footer-section {
      margin-top: 30px;
      padding-top: 15px;
      border-top: 2px solid black;
      display: flex;
      justify-content: space-between;
    }
    
    .footer-left, .footer-center, .footer-right {
      flex: 1;
      padding: 0 15px;
    }
    
    .footer-center {
      text-align: center;
    }
    
    .footer-right {
      text-align: right;
    }
    
    .footer-title {
      font-weight: bold;
      margin-bottom: 10px;
      font-size: 12px;
    }
    
    .signature-line {
      border-bottom: 1px solid black;
      width: 150px;
      height: 40px;
      margin-top: 15px;
    }
    
    .small-text {
      font-size: 10px;
      color: #666;
      line-height: 1.4;
    }
    
    @media print {
      body {
        background: white !important;
        -webkit-print-color-adjust: exact;
      }
    }
  </style>
</head>
<body>

<div class="payslip-container">
  <!-- Header Section -->
  <div class="header-section">
    <didiv class="header-top">
      <div class="company-info">
        <div class="company-name"><?= $payslip['company_name'] ?? 'Your Company Name' ?></div>
        <div class="small-text">
          Employee Management System<br>
          Payroll Department
        </div>
      </div>
      
      <div class="logo-section">
        <div class="logo-placeholder">LOGO</div>
      </div>
      
      <div class="payslip-info">
        <div class="payslip-title">PAY SLIP</div>
        <div class="payslip-title" style="font-size: 14px;">สลิปเงินเดือน</div>
        <div class="small-text">
          Payslip #<?= $payslip['id'] ?><br>
          <?= date('F Y', strtotime($payslip['payment_date'])) ?>
        </div>
      </div>
    </div>
    
    <div class="employee-details">
      <div class="employee-left">
        <strong>Employee:</strong> <?= $payslip['employee_name'] ?? 'N/A' ?><br>
        <strong>พนักงาน:</strong><br>
        <strong>Employee ID:</strong> <?= $payslip['employee_id'] ?? 'N/A' ?><br>
        <strong>Position:</strong> <?= $payslip['position'] ?? 'N/A' ?>
      </div>
      
      <div class="employee-right">
        <strong>Salary Period:</strong> <?= $payslip['salary_period'] ?><br>
        <strong>รอบเงินเดือน:</strong><br>
        <strong>Payment Date:</strong> <?= date('d/m/Y', strtotime($payslip['payment_date'])) ?><br>
        <strong>Bank Account:</strong> <?= $payslip['bank_account'] ?? 'N/A' ?>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Earnings Section -->
    <div class="earnings-section">
      <div class="section-title">EARNINGS • เงินได้</div>
      <div class="section-content">
        <div class="item-row">
          <div class="item-label">
            Salary/Wage
            <span class="thai-label">เงินเดือน/ค่าจ้าง</span>
          </div>
          <div class="item-value">฿<?= number_format($earnings['salary'] ?? 0, 2) ?></div>
        </div>
        
        <div class="item-row">
          <div class="item-label">
            Overtime
            <span class="thai-label">ค่าล่วงเวลา</span>
          </div>
          <div class="item-value">฿<?= number_format($earnings['overtime'] ?? 0, 2) ?></div>
        </div>
        
        <div class="item-row">
          <div class="item-label">
            Allowance
            <span class="thai-label">ค่าเบี้ยเลี้ยง</span>
          </div>
          <div class="item-value">฿<?= number_format($earnings['allowance'] ?? 0, 2) ?></div>
        </div>
        
        <div class="item-row">
          <div class="item-label">
            Bonus
            <span class="thai-label">โบนัส</span>
          </div>
          <div class="item-value">฿<?= number_format($earnings['bonus'] ?? 0, 2) ?></div>
        </div>
        
        <div class="item-row">
          <div class="item-label">
            Other Earnings
            <span class="thai-label">เงินได้อื่นๆ</span>
          </div>
          <div class="item-value">฿<?= number_format($earnings['earning_other'] ?? 0, 2) ?></div>
        </div>
        
        <div class="total-row">
          <div class="item-row">
            <div class="item-label">
              Total Earnings
              <span class="thai-label">รวมเงินได้</span>
            </div>
            <div class="item-value">฿<?= number_format($earnings['total_earning'] ?? 0, 2) ?></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Deductions Section -->
    <div class="deductions-section">
      <div class="section-title">DEDUCTIONS • รายการหัก</div>
      <div class="section-content">
        <div class="item-row">
          <div class="item-label">
            Social Security
            <span class="thai-label">ประกันสังคม</span>
          </div>
          <div class="item-value">฿<?= number_format($deductions['social_security'] ?? 0, 2) ?></div>
        </div>
        
        <div class="item-row">
          <div class="item-label">
            Tax
            <span class="thai-label">ภาษี</span>
          </div>
          <div class="item-value">฿<?= number_format($deductions['tax'] ?? 0, 2) ?></div>
        </div>
        
        <div class="item-row">
          <div class="item-label">
            Other Deductions
            <span class="thai-label">รายการหักอื่นๆ</span>
          </div>
          <div class="item-value">฿<?= number_format($deductions['deduction_other'] ?? 0, 2) ?></div>
        </div>
        
        <div class="total-row">
          <div class="item-row">
            <div class="item-label">
              Total Deductions
              <span class="thai-label">รวมรายการหัก</span>
            </div>
            <div class="item-value">฿<?= number_format($deductions['total_deduction'] ?? 0, 2) ?></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Summary Section -->
    <div class="summary-section">
      <div class="summary-left">
        <div class="summary-title">YEAR TO DATE • สะสมรายปี</div>
        <div class="item-row">
          <div class="item-label">
            Year
            <span class="thai-label">ปี</span>
          </div>
          <div class="item-value"><?= $payslip['year'] ?></div>
        </div>
        <div class="item-row">
          <div class="item-label">
            YTD Earnings
            <span class="thai-label">เงินได้สะสม</span>
          </div>
          <div class="item-value">฿<?= number_format($earnings['ytd_earning'] ?? 0, 2) ?></div>
        </div>
      </div>
      
      <div class="summary-right">
        <div class="summary-title">PAYSLIP INFO • ข้อมูลสลิป</div>
        <div class="item-row">
          <div class="item-label">
            Payslip ID
            <span class="thai-label">เลขที่สลิป</span>
          </div>
          <div class="item-value">#<?= $payslip['id'] ?></div>
        </div>
        <div class="item-row">
          <div class="item-label">
            Generated
            <span class="thai-label">วันที่สร้าง</span>
          </div>
          <div class="item-value"><?= date('d/m/Y') ?></div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Net Pay Section -->
  <div class="net-pay-section">
    <div class="net-pay-label">NET PAY • เงินได้สุทธิ</div>
    <div class="net-pay-amount">฿<?= number_format($payslip['netpay'], 2) ?></div>
  </div>
  
  <!-- Footer Section -->
  <div class="footer-section">
    <div class="footer-left">
      <div class="footer-title">
        Remarks • หมายเหตุ
      </div>
      <div class="small-text">
        <?= $payslip['employee_signature'] ?: 'ไม่มีหมายเหตุเพิ่มเติม' ?><br><br>
        This payslip is computer generated and does not require a signature.
      </div>
    </div>
    
    <div class="footer-center">
      <div class="footer-title">Important Document</div>
      <div class="small-text">
        Keep this payslip for your records<br>
        เก็บสลิปนี้ไว้เป็นหลักฐาน<br><br>
        This is an official payroll document<br>
        issued by the HR Department
      </div>
    </div>
    
    <div class="footer-right">
      <div class="footer-title">
        Authorized Signature<br>
        ลายเซ็นผู้มีอำนาจ
      </div>
      <div class="signature-line"></div>
      <div class="small-text">
        HR Manager<br>
        Date: <?= date('d/m/Y') ?>
      </div>
    </div>
  </div>
</div>

</body>
</html>