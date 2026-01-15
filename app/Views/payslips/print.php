<!DOCTYPE html>
<html>
<head>
  <title>Payslip #<?= $payslip['id'] ?></title>
  <meta charset="UTF-8">
 <style>
  /* Hide browser print headers and footers */
  @page {
    margin: 0;
    size: A4;
  }
  
  @media print {
    body {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    
    /* Hide browser default headers and footers */
    @page {
      margin: 0;
      size: A4;
    }
    
    /* Ensure no page breaks within payslip */
    .payslip-container {
      page-break-inside: avoid;
    }
  }

  body {
    font-family: 'Sarabun', Arial, sans-serif;
    margin: 0;
    padding: 20px;
    color: #000;
    font-size: 12px;
    line-height: 1.4;
  }
  .logo-placeholder {
 
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

  .payslip-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0;
  }

  .header-section {
    display: flex;
    align-items: flex-start;
    border-bottom: none;
    margin-bottom: 0px;
  }

  .header-left,
  .header-center,
  .header-right {
    flex: 1;
    padding: 10px;
  }

  .header-center {
    text-align: center;
    padding-top: 0;
    margin-right:60px;
  }
  
  .header-left {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin-bottom:-10px
  }

  .header-right {
    text-align: right;
    padding-top: 40px;
    margin-left: 10px;
    padding-right:0px
    
  }

  .company-name {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 5px;
  }

  .logo-placeholder {
  
  }

  .payslip-title {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 5px;
    text-align:left;
  }

  .thai-text {
    font-size: 10px;
    color: #666;
  }

  .main-content {
    display: flex;
    border-left: 1px solid #000;
    border-right: 1px solid #000;
    border-top: 1px solid #000;
    border-bottom:1px solid #000;
   
  }

  .column {
    flex: 1;
    padding: 15px;
    border-left: 1px solid #000;
    border-bottom: 1px solid #000;
  }

  .column:first-child {
    border-left: none;
  }

 .column-header {
  background: none;
  text-align: center;
  font-weight: bold;
  font-size: 13px;
  padding: 6px 0 8px 0;
  margin-bottom: 10px;
  border-bottom: 1px solid #000;
}


  .item-row {
    display: flex;
    margin-bottom: 4px;
  }

  .item-label {
    flex: 1;
    font-size: 12px;
  }

  .item-value {
    width: 80px;
    text-align: right;
    font-size: 12px;
  }

  .total-row {
    margin-top: 10px;
    padding-top: 5px;
    font-weight: bold;
  }

  .net-pay-block {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px solid #000;
    text-align: center;
  }

  .net-pay-amount {
    font-size: 16px;
    color: #000 !important;
    font-weight: bold;
    color: #1a4d1a;
    margin-top: 0px;
    margin-left: 40px;
    padding-top:0px; 
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: bold;
    font-size: 14px;
    font-weight: bold;
    
  }

  .footer-section {
    display: flex;
    padding: 15px;
    border-top: 2px solid #000;
    font-size: 11px;
  }

  .footer-left,
  .footer-center,
  .footer-right {
    flex: 1;
  }

  .footer-center {
    text-align: center;
    line-height: 1.8;
    font-size: 11px;
  }

  .footer-center .dotted-line {
    display: block;
    margin: 0 auto;
    border-top: 1px solid #000;
    width: 100%;
  
    font-size: 10px; 
    color: #000;
    letter-spacing: 2px;
  }

  .footer-right {
    text-align: left;
    font-size: 11px;
    left: auto;
    margin-left: 220px;
    right: 100px;
    padding-right: 10px;
    padding-left: 0;
    padding-bottom: 10px;
  }

  .signature-line {
    border-bottom: 1px solid #000;
    width: 150px;
    margin-left: 92px;
    left: 10px;
  }

  .small-text {
    font-size: 10px;
    color: #666;
  }
  #logo{
   max-width: 120px;
   height: auto;
   margin-bottom: 10px;
  }

  .company-info {
    font-size: 12px;
    line-height: 1.5;
    text-align: center; 
    font-weight: bold;
    padding: 10px;
    padding-top: 80px;
    display: flex;
    position: absolute;
    justify-content: space-between;
    align-items: center;
    left: 30%;
    top: 3%;
  }
  .company-info h1 {
    font-size: 16px;
    margin: 0;
    padding: 0;
    font-weight: bold;
    color: #000;
  }
  .company-info p {
    font-size: 12px;
    margin: 0;
    padding: 0;
    color: #000;
  }
  .employee-info {
    font-size: 1;
    text-align: left;
  
  
    }

   .fontCharacter {
    font-weight: bolder !important;
    color: #000;
    text-align: left;
   }

  .employee-info {
    font-size: 10px;
    line-height: 1.3;
    padding-top: 10px;
    padding-bottom: 10px;
    padding-left: 10px;
    padding-right: 10px;
    margin: 0 auto;
    width: 100%;
    background-color: #fff;
    border-radius: 10px
  }
  
 .netpaid {
   font-weight: 900 !important;
   font-size: 14px; 
 }

 /* Additional print-specific styles */
 @media print {
   /* Remove any browser-added margins */
   html, body {
     margin: 0 !important;
     padding: 0 !important;
     height: 100% !important;
     overflow: hidden !important;
   }
   
   /* Ensure clean print layout */
   .payslip-container {
     margin: 20px !important;
     padding: 0 !important;
     box-shadow: none !important;
   }
   
   /* Hide any potential browser elements */
   header, nav, aside, .no-print, #print-instructions {
     display: none !important;
   }
 }
</style>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Roboto', sans-serif;
    }
  </style>
</head>
<body>
 <!-- Print Instructions (hidden in print) -->
 <div id="print-instructions" style="position: fixed; top: 10px; right: 10px; background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; font-size: 12px; z-index: 1000; display: none;">
   <strong>Print Settings:</strong><br>
   1. Uncheck "Headers and footers"<br>
   2. Set margins to "None" or "Minimum"<br>
   <button onclick="this.parentElement.style.display='none'" style="margin-top: 5px; padding: 2px 8px; font-size: 10px;">Got it</button>
 </div>

 <div class="company-info">
        ห้างหุ้นส่วนจำกัด รูบี้ช๊อป (สำนักงานใหญ่)<br>
        97/60 หมู่บ้านหลักสี่แลนด์ ซอยโกสุมรวมใจ 39 <br>
        แขวงดอนเมือง เขตดอนเมือง กรุงเทพมหานคร 10210 <br>
        เลขที่ผู้เสียภาษี: 0103555019171
      </div>
<div class="payslip-container">
  <!-- Company Address at Top Center -->

  
  <!-- Header Section -->
  <div class="header-section">
    
    <!-- Left: Logo + Employee Info -->
    <div class="header-left">
      <div class="logo-placeholder" style="border: none; padding: 0; margin-bottom: 15px;">
        <img src="https://www.rubyshop.co.th/storage/logo/rubyshop-no-bg-250pxx100px.jpg" id="logo">
      </div>
      <div class="employee-info">
        <strong>ชื่อพนักงาน:</strong> <?= $payslip['employee_name'] ?? 'N/A' ?><br>
        <span class="small-text">Employee name</span><br><br>
        <strong>รหัสพนักงาน:</strong> <?= $payslip['employee_id'] ?? 'N/A' ?><br>
        <span class="small-text">Employee ID</span><br><br>
        <strong>ตำแหน่ง:</strong> <?= $payslip['position'] ?? 'N/A' ?><br>
        <span class="small-text">Position</span>
      </div>
    </div>

    <!-- Center: Company Info -->
    <div class="header-center">
     
    </div>

  

    <!-- Right: Payslip Info -->
    <div class="header-right">
      <div class="payslip-title" style="margin-bottom: 15px;">สลิปเงินเดือน / Pay Slip</div>
      <div class="small-text fontCharacter">
        <strong>รอบเงินเดือน:</strong> <?= $payslip['salary_period'] ?><br>
        <span class="thai-text">Salary Period</span><br><br>
        <strong>วันที่จ่ายเงินเดือน:</strong> <?= date('d/m/Y', strtotime($payslip['payment_date'])) ?><br>
        <span class="thai-text">Payment date</span><br><br>
        <strong>บัญชีธนาคาร:</strong> <?= $payslip['bank_account'] ?? 'N/A' ?><br>
        <span class="thai-text">Bank account</span>
      </div>
    </div>
  </div>


  <!-- Main Content - 3 Columns -->
  <div class="main-content">
    <!-- Left Column - Earnings -->
    <div class="column">
      <div class="column-header">
        รายได้<br>
        <span class="thai-text">Earnings</span>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          เงินเดือน/ค่าจ้าง<br>
          <span class="small-text">Salary/wage</span>
        </div>
        <div class="item-value"><?= number_format($earnings['salary'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          ค่าล่วงเวลา<br>
          <span class="small-text">Overtime</span>
        </div>
        <div class="item-value"><?= number_format($earnings['overtime'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          คอมมิชชั่น<br>
          <span class="small-text">Commission</span>
        </div>
        <div class="item-value"><?= number_format($earnings['commission'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          ค่าเบี้ยเลี้ยง<br>
          <span class="small-text">Allowance</span>
        </div>
        <div class="item-value"><?= number_format($earnings['allowance'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          โบนัส<br>
          <span class="small-text">Bonus</span>
        </div>
        <div class="item-value"><?= number_format($earnings['bonus'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          เงินได้อื่นๆ<br>
          <span class="small-text">Other</span>
        </div>
        <div class="item-value"><?= number_format($earnings['earning_other'] ?? 0, 2) ?></div>
      </div>
    </div>
    
    <!-- Middle Column - Deductions -->
    <div class="column">
      <div class="column-header">
        รายการหัก<br>
        <span class="thai-text">Deductions</span>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          ประกันสังคม<br>
          <span class="small-text">Social Security fund</span>
        </div>
        <div class="item-value"><?= number_format($deductions['social_security'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          ภาษี หัก ณ ที่จ่าย 3 %<br>
          <span class="small-text">Tax</span>
        </div>
        <div class="item-value"><?= number_format($deductions['tax'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          กองทุนสำรองเลี้ยงชีพ<br>
          <span class="small-text">Provident Fund</span>
        </div>
        <div class="item-value"><?= number_format($deductions['provident_fund'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          กองทุนสวัสดิการพนักงาน<br>
          <span class="small-text">Employee Welfare Fund</span>
        </div>
        <div class="item-value"><?= number_format($deductions['employee-welfare-fund'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          หักเงินเบิกล่วงหน้า<br>
          <span class="small-text">Advance Deduction</span>
        </div>
        <div class="item-value"><?= number_format($deductions['advance-deduction'] ?? 0, 2) ?></div>
      </div>
      
      <?php if (($deductions['loan_repayment'] ?? 0) > 0): ?>
      <div class="item-row">
        <div class="item-label">
          ชำระคืนเงินกู้บริษัท<br>
          <span class="small-text">Loan Repayment</span>
        </div>
        <div class="item-value"><?= number_format($deductions['loan_repayment'] ?? 0, 2) ?></div>
      </div>
      <?php endif; ?>
      
      <div class="item-row">
        <div class="item-label">
          รายการหักอื่นๆ<br>
          <span class="small-text">Other Deductions</span>
        </div>
        <div class="item-value"><?= number_format($deductions['deduction_other'] ?? 0, 2) ?></div>
      </div>
    </div>
    
    <!-- Right Column - Summary -->
    <div class="column">
      <div class="column-header">
        สรุป<br>
        <span class="thai-text">Summary</span>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          ปี<br>
          <span class="small-text">Year</span>
        </div>
        <div class="item-value"><?= $payslip['year'] ?></div>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          เงินได้สะสม<br>
          <span class="small-text">YTD Earnings</span>
        </div>
        <div class="item-value"><?= number_format($earnings['ytd_earning'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          ประกันสังคมสะสม<br>
          <span class="small-text">Accumulated SSF</span>
        </div>
        <div class="item-value"><?= number_format($earnings['accumulated-ssf'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row">
        <div class="item-label">
          ภาษีหักสะสม<br>
          <span class="small-text">YTD Withholding Tax</span>
        </div>
        <div class="item-value"><?= number_format($earnings['ytd-withholding-tax'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row total-row">
        <div class="item-label">
          รวมเงินได้<br>
          <span class="small-text">Total Earnings</span>
        </div>
        <div class="item-value"><?= number_format($earnings['total_earning'] ?? 0, 2) ?></div>
      </div>
      
      <div class="item-row total-row">
        <div class="item-label">
          รวมรายการหัก<br>
          <span class="small-text">Total deductions</span>
        </div>
        <div class="item-value"><?= number_format($deductions['total_deduction'] ?? 0, 2) ?></div>
      </div>
      <div class="item-row total-row">
  เงินได้สุทธิ / Net pay<br>
  <span class="item-value netpaid"><?= number_format($payslip['netpay'], 2) ?></span>
</div>

    </div>
  </div>
  
  <!-- Net Pay Section -->
  <!-- <div class="net-pay-section">
    <div style="font-size: 14px; margin-bottom: 5px;">
      เงินได้สุทธิ / Net pay
    </div>
    <div class="net-pay-amount">
      ฿<?= number_format($payslip['netpay'], 2) ?>
    </div>
  </div> -->
  
  <!-- Footer Section -->
  <div class="footer-section">
    <div class="footer-left">
      <div style="font-weight: bold; margin-bottom: 10px;">
        หมายเหตุ<br>
        <span class="small-text">Remark:   <?= $payslip['remark'] ? $payslip['remark'] : 'ไม่มีหมายเหตุเพิ่มเติม' ?></span>
        <div class="small-text">
      
      </div>
      </div>
     
    </div>
    
    

    
    <div class="footer-right">
      <div style="font-weight: bold; margin-bottom: 10px;">
        ลายเซ็นผู้รับเงิน:<br>
        <span class="small-text">Employee Signature:</span> <div class="signature-line"></div>
      </div>
      
      <div class="small-text" style="margin-top: 5px;">
        วันที่: <?= date('d/m/Y') ?>
      </div>
    </div>
  


</div>
<div class="footer-center">
 <div class="dotted-line"></div>
  <p class="small-text">ข้อมูลในสลิปเงินเดือนนี้ถือเป็นความลับเฉพาะบุคคล ห้ามเผยแพร่หรือเปิดเผยต่อบุคคลภายนอก กรุณาเก็บไว้เป็นอย่างดี หากมีข้อผิดพลาดหรือมีข้อสงสัยเกี่ยวกับสลิปเงินเดือนกรุณาติดต่อฝ่ายทรัพยากรบุคคล</p>
  <p class="small-text">
The information in this payslip is confidential and may not be shared or disclosed to outsiders. Please keep it safe. If there are any errors or questions regarding the payslip, please contact the HR.</p>
</div>
</div>

//  <script type="text/javascript">
//     // Auto-trigger print dialog when page loads
//     window.print()
    
//     // Handle print dialog close
//     window.onafterprint = function() {
       
//         window.close();
//     };
// </script> 

</body>
</html>
