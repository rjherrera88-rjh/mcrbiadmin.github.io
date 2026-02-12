<?php
session_start();
include "db.php";
date_default_timezone_set('Asia/Manila');

/* AUTO DAILY RESET */
$reset_file = "last_reset.txt";
$today = date("Y-m-d");

if(!file_exists($reset_file)){
    file_put_contents($reset_file,$today);
}

$last = trim(file_get_contents($reset_file));

if($last != $today){
    $conn->query("TRUNCATE TABLE transaction");
    file_put_contents($reset_file,$today);
}

/* MANUAL RESET */
if (isset($_GET['reset'])) {
    $conn->query("TRUNCATE TABLE transaction");
    header("Location: admin_dashboard.php");
    exit();
}

/* SEARCH */
$search = "";
$where = "";

if(isset($_GET['search']) && $_GET['search']!=""){
    $search = $conn->real_escape_string($_GET['search']);
    $where = " AND (Fullname LIKE '%$search%' 
                OR transaction_type LIKE '%$search%')";
}

/* APPROVE */
if (isset($_GET['approve'])) {
    $id=intval($_GET['approve']);
    $conn->query("UPDATE transaction SET status='APPROVED' WHERE id=$id");
    header("Location: admin_dashboard.php");
    exit();
}

/* REJECT */
if (isset($_GET['reject'])) {
    $id=intval($_GET['reject']);
    $conn->query("UPDATE transaction SET status='REJECTED' WHERE id=$id");
    header("Location: admin_dashboard.php");
    exit();
}

/* QUERIES */
$pending = $conn->query("SELECT * FROM transaction 
                         WHERE status='PENDING' $where
                         ORDER BY created_at ASC");

$approved = $conn->query("SELECT * FROM transaction 
                          WHERE status='APPROVED' $where
                          ORDER BY created_at DESC");

/* SUMMARY */
$result_total = $conn->query("SELECT COUNT(*) as total FROM transaction");
$row_total = $result_total->fetch_assoc();
$total_transactions = $row_total['total'];
$result_pending = $conn->query("SELECT COUNT(*) as total FROM transaction WHERE status='PENDING'");
$row_pending = $result_pending->fetch_assoc();
$total_pending = $row_pending['total'];
$result_approved = $conn->query("SELECT COUNT(*) as total FROM transaction WHERE status='APPROVED'");
$row_approved = $result_approved->fetch_assoc();
$total_approved = $row_approved['total'];


/* ✅ NEW: REJECTED */
$result_rejected = $conn->query("SELECT COUNT(*) as total FROM transaction WHERE status='REJECTED'");
$row_rejected = $result_rejected->fetch_assoc();
$total_rejected = $row_rejected['total'];


/* ANALYTICS */
$analytics = $conn->query("
    SELECT transaction_type, COUNT(*) as total 
    FROM transaction
    GROUP BY transaction_type
");

$labels=array();
$data=array();
while($a=$analytics->fetch_assoc()){
    $labels[]=$a['transaction_type'];
    $data[]=$a['total'];
}

/* STATUS PIE */
$status_query = $conn->query("SELECT status, COUNT(*) as total FROM transaction GROUP BY status");
$status_labels = array();
$status_data = array();
while($row=$status_query->fetch_assoc()){
    $status_labels[]=$row['status'];
    $status_data[]=$row['total'];
}

/* DAILY */
$daily_query = $conn->query("
    SELECT DATE(created_at) as day, COUNT(*) as total
    FROM transaction
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");

$daily_labels = array();
$daily_data = array();
while($row=$daily_query->fetch_assoc()){
    $daily_labels[]=$row['day'];
    $daily_data[]=$row['total'];
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
*{box-sizing:border-box;}

body{
    font-family:Arial;
    background:#f2f2f2;
    margin:0;
    display:flex;
    min-height:100vh;
}

/* SIDEBAR */
.top-header{
    background:#5F2521;
    width:260px;
    min-height:100vh;
    text-align:center;
    padding:80px 20px;
    flex-shrink:0;
}

.bank-logo{width:120px;}

.bank-name{
    font-weight:800;
    font-size:18px;
    color:white;
    margin-top:15px;
}

/* MAIN */
.container{
    flex:1;
    padding:40px;
    background:#fff;
    overflow-x:auto;
}

/* ===== NEW SUMMARY CARDS ===== */
.analytics-cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:15px;
    margin-bottom:30px;
}

.card{
    background:#926b00;
    color:white;
    padding:20px;
    border-radius:10px;
    text-align:center;
    font-weight:bold;
}

.card h2{
    margin:10px 0 0 0;
}

/* FORM */
form{
    margin-bottom:15px;
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:8px;
}

form input{
    padding:8px;
    border-radius:6px;
    border:1px solid #ccc;
    width:220px;
}

form button,
form a{
    padding:8px 15px;
    border:none;
    border-radius:6px;
    font-weight:bold;
    text-decoration:none;
    color:white;
    cursor:pointer;
}

form button{background:#926b00;}
form a{background:#6c757d;}
.reset-btn{background:#007bff;}
.logout-btn{background:#dc3545;}

.form-right{
    margin-left:auto;
    display:flex;
    gap:8px;
}

/* TABLE */
.table-wrapper{overflow-x:auto;}

.table-header,.table-row{
    display:grid;
    grid-template-columns:repeat(8,1fr);
    text-align:center;
    min-width:1000px;
}

.table-header{
    background:#926b00;
    color:white;
    padding:15px;
    font-weight:bold;
}

.table-row{
    padding:15px;
    border-bottom:1px solid #eee;
}

.status-pending{color:orange;font-weight:bold;}
.status-approved{color:green;font-weight:bold;}

.actions{
    display:flex;
    justify-content:center;
    align-items:center;
    flex-wrap:wrap;
}

.actions a{
    padding:6px 12px;
    border-radius:20px;
    text-decoration:none;
    font-size:12px;
    margin:2px;
    color:white;
}

.approve{background:#926b00;}
.reject{background:#6c757d;}

.completed-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:40px;
    margin-bottom:10px;
    flex-wrap:wrap;
}

.download-btn{
    background:#28a745;
    color:white;
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
    font-weight:bold;
}

/* NEW CHART GRID */
.chart-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
    gap:25px;
    margin-top:20px;
}
#statusChart{
    max-width:300px;
    max-height:300px;
    margin:auto;
}

/* RESPONSIVE */
@media(max-width:1024px){
    body{flex-direction:column;}
    .top-header{
        width:100%;
        min-height:auto;
        padding:20px;
    }
    .container{padding:20px;}
    .form-right{
        width:100%;
        margin-left:0;
        justify-content:flex-end;
    }
}
</style>
</head>

<body>

<div class="top-header">
    <img src="mcrbi-logo.png" class="bank-logo">
    <div class="bank-name">MOUNT CARMEL RURAL BANK INC.</div>
</div>

<div class="container">
<h1>Admin Dashboard</h1>

<!-- SUMMARY CARDS -->
<div class="analytics-cards">
    <div class="card">Total Transactions<h2><?php echo $total_transactions; ?></h2></div>
    <div class="card">Approved<h2><?php echo $total_approved; ?></h2></div>
    <div class="card">Rejected<h2><?php echo $total_rejected; ?></h2></div>
    <div class="card">Pending<h2><?php echo $total_pending; ?></h2></div>
</div>


<form method="GET">
<input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search name or transaction type">
<button>Search</button>
<a href="admin_dashboard.php">Refresh</a>
<div class="form-right">
<a href="?reset=1" class="reset-btn">Reset</a>
<a href="logout.php" class="logout-btn">Logout</a>
</div>
</form>

<div class="section-title"><strong>Pending Queue</strong></div>
<div class="table-wrapper">
<div class="table-header">
<div>Name</div><div>Type</div><div>Account / Product</div><div>Amount</div>
<div>Time Request</div><div>Pending For</div><div>Status</div><div>Action</div>
</div>

<?php while($row=$pending->fetch_assoc()){ ?>
<div class="table-row">
<div><?php echo $row['Fullname']; ?></div>
<div><?php echo $row['transaction_type']; ?></div>
<div>
<?php
if(!empty($row['account_number'])) echo "Acct: ".$row['account_number']."<br>";
if(!empty($row['product_number'])) echo "Product: ".$row['product_number'];
?>
</div>
<div>₱ <?php echo number_format($row['amount'],2); ?></div>
<div><?php echo $row['created_at']; ?></div>
<div class="pending-time" data-time="<?php echo strtotime($row['created_at']); ?>"></div>
<div class="status-pending">PENDING</div>
<div class="actions">
<a href="?approve=<?php echo $row['id']; ?>" class="approve">Approve</a>
<a href="?reject=<?php echo $row['id']; ?>" class="reject">Reject</a>
</div>
</div>
<?php } ?>
</div>

<div class="completed-header">
<h3>Completed Transactions</h3>
<a href="export_excel.php" class="download-btn">Download Report</a>
</div>

<div class="table-wrapper">
<div class="table-header">
<div>Name</div><div>Type</div><div>Account / Product</div>
<div>Amount</div><div>Time Request</div><div>Status</div><div></div><div></div>
</div>

<?php while($row=$approved->fetch_assoc()){ ?>
<div class="table-row">
<div><?php echo $row['Fullname']; ?></div>
<div><?php echo $row['transaction_type']; ?></div>
<div>
<?php
if(!empty($row['account_number'])) echo "Acct: ".$row['account_number']."<br>";
if(!empty($row['product_number'])) echo "Product: ".$row['product_number'];
?>
</div>
<div>₱ <?php echo number_format($row['amount'],2); ?></div>
<div><?php echo $row['created_at']; ?></div>
<div class="status-approved">APPROVED</div>
<div></div><div></div>
</div>
<?php } ?>
</div>

<h3 style="margin-top:40px;">Transactions Analytics</h3>
<canvas id="analyticsChart" height="80"></canvas>

<div class="chart-grid">
<canvas id="statusChart"></canvas>
<canvas id="dailyChart"></canvas>
</div>

</div>

<script>
function updateTimers(){
let now = Math.floor(Date.now()/1000);
document.querySelectorAll(".pending-time").forEach(el=>{
let created = parseInt(el.getAttribute("data-time"));
let diff = now - created;
let h = Math.floor(diff/3600);
let m = Math.floor((diff%3600)/60);
let s = diff%60;
el.innerHTML = h+"h "+m+"m "+s+"s";
});
}
setInterval(updateTimers,1000);
updateTimers();

/* ORIGINAL BAR */
new Chart(document.getElementById('analyticsChart'),{
type:'bar',
data:{
labels: <?php echo json_encode($labels); ?>,
datasets:[{label:'Transactions Count',data: <?php echo json_encode($data); ?>}]
}
});

/* PIE */
new Chart(document.getElementById('statusChart'),{
type:'pie',
data:{
labels: <?php echo json_encode($status_labels); ?>,
datasets:[{data: <?php echo json_encode($status_data); ?>}]
}
});

/* LINE */
new Chart(document.getElementById('dailyChart'),{
type:'line',
data:{
labels: <?php echo json_encode($daily_labels); ?>,
datasets:[{label:'Daily Transactions',data: <?php echo json_encode($daily_data); ?>}]
}
});
</script>

</body>
</html>
