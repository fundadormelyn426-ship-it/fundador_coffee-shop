<?php
require_once 'config.php';
require_admin();

$orderId=(int)($_GET['order']??0);
$search=trim($_GET['q']??'');
$params=[];
$sql="SELECT o.*, GROUP_CONCAT(CONCAT(oi.product_name,' ×',oi.quantity) ORDER BY oi.id SEPARATOR ' | ') AS items
      FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id";
if($search!==''){
  $sql.=" WHERE o.customer_name LIKE ? OR o.customer_email LIKE ? OR oi.product_name LIKE ?";
  $like='%'.$search.'%'; $params=[$like,$like,$like];
}
$sql.=" GROUP BY o.id ORDER BY o.created_at DESC";
$s=$pdo->prepare($sql); $s->execute($params); $orders=$s->fetchAll();
$detail=null; $detailItems=[];
if($orderId){
  $s=$pdo->prepare('SELECT * FROM orders WHERE id=?'); $s->execute([$orderId]); $detail=$s->fetch();
  if($detail){$s=$pdo->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id');$s->execute([$orderId]);$detailItems=$s->fetchAll();}
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Order History - Fundador Coffee</title><link rel="stylesheet" href="style.css"><style>
.admin{padding:28px;width:min(1200px,94%);margin:auto}.box{background:#fffaf3;padding:20px;border-radius:10px;margin-bottom:20px}.table{width:100%;border-collapse:collapse}.table td,.table th{padding:10px;border-bottom:1px solid #dfcdb9;text-align:left;vertical-align:top}.btn{background:#28551e;color:#fff;border:0;border-radius:6px;padding:8px 12px;text-decoration:none;cursor:pointer}.search{display:flex;gap:8px;margin-bottom:16px}.search input{padding:10px;flex:1}.muted{color:#777}.status{font-weight:700}.detail-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.detail-grid div{padding:12px;background:#f5eadc;border-radius:8px}@media(max-width:850px){.detail-grid{grid-template-columns:1fr 1fr}.table{min-width:850px}.scroll{overflow:auto}}@media(max-width:520px){.detail-grid{grid-template-columns:1fr}}
</style></head><body>
<header class="header"><div class="nav"><a class="logo" href="index.php"><span class="cup">☕</span><span><b>FUNDADOR</b><small>COFFEE SHOP</small></span></a><nav><a href="index.php">Shop</a><a class="active" href="history.php">Order History</a><a href="admin.php">Admin</a><a href="logout.php">Logout</a></nav></div></header>
<main class="admin"><h1>Order History</h1><p class="muted">View who ordered each product, quantities, totals, status, and order date.</p>
<section class="box"><form class="search" method="get"><input name="q" value="<?=h($search)?>" placeholder="Search customer, email, or product..."><button class="btn">Search</button><?php if($search):?><a class="btn" href="history.php">Clear</a><?php endif;?></form>
<div class="scroll"><table class="table"><tr><th>Order</th><th>Customer</th><th>Products</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr>
<?php foreach($orders as $o):?><tr><td><b>#<?=$o['id']?></b></td><td><b><?=h($o['customer_name'])?></b><br><?=h($o['customer_email'])?></td><td><?=h($o['items']??'')?></td><td>₱<?=number_format($o['total'],2)?></td><td class="status"><?=h($o['status'])?></td><td><?=h($o['created_at'])?></td><td><a class="btn" href="history.php?order=<?=$o['id']?><?= $search?'&q='.urlencode($search):'' ?>">View</a></td></tr><?php endforeach; ?>
<?php if(!$orders):?><tr><td colspan="7">No order history found.</td></tr><?php endif;?></table></div></section>
<?php if($detail):?><section class="box"><h2>Order #<?=$detail['id']?></h2><div class="detail-grid"><div><small>Customer</small><br><b><?=h($detail['customer_name'])?></b></div><div><small>Email</small><br><?=h($detail['customer_email'])?></div><div><small>Status</small><br><b><?=h($detail['status'])?></b></div><div><small>Date</small><br><?=h($detail['created_at'])?></div></div><h3>Products Ordered</h3><div class="scroll"><table class="table"><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th></tr><?php foreach($detailItems as $i):?><tr><td><?=h($i['product_name'])?></td><td>₱<?=number_format($i['price'],2)?></td><td><?=h($i['quantity'])?></td><td>₱<?=number_format($i['subtotal'],2)?></td></tr><?php endforeach;?><tr><th colspan="3">Total</th><th>₱<?=number_format($detail['total'],2)?></th></tr></table></div></section><?php endif;?>
</main></body></html>
