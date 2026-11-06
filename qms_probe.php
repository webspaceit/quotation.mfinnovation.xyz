<?php
try {
  $p = new PDO("mysql:host=localhost;dbname=qms_db;charset=utf8mb4","root","");
  $p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { echo "DB CONNECT FAILED: ".$e->getMessage()."\n"; exit; }
foreach (["users","clients","quotations","quotation_items","email_logs","company_settings"] as $t) {
  try { echo $t." = ".$p->query("SELECT COUNT(*) FROM ".$t)->fetchColumn()."\n"; }
  catch (Exception $e) { echo $t." = MISSING\n"; }
}
$row = $p->query("SELECT quotation_number,status,grand_total,quotation_date FROM quotations ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "\nRecent:\n"; foreach($row as $r){ echo "  ".$r["quotation_number"]." | ".$r["status"]." | ".$r["grand_total"]." | ".$r["quotation_date"]."\n"; }
