<?php
require_once __DIR__.'/validation.php';
function cart_items(PDO $pdo){
    $cart=$_SESSION['cart']??[]; $items=[];$total=0;$count=0;
    foreach($cart as $id=>$qty){
        $s=$pdo->prepare('SELECT id,name,price,image,stock FROM products WHERE id=? AND is_active=1');$s->execute([(int)$id]);$p=$s->fetch();
        if(!$p || (int)$p['stock']<=0){unset($_SESSION['cart'][$id]);continue;}
        $qty=min((int)$qty,(int)$p['stock']); if($qty<1){unset($_SESSION['cart'][$id]);continue;}
        $_SESSION['cart'][$id]=$qty; $sub=(float)$p['price']*$qty; $total+=$sub;$count+=$qty;
        $items[]=['id'=>(int)$p['id'],'name'=>$p['name'],'price'=>(float)$p['price'],'image'=>$p['image'],'stock'=>(int)$p['stock'],'qty'=>$qty,'subtotal'=>$sub];
    }
    return ['items'=>$items,'total'=>$total,'count'=>$count];
}
function add_stock(PDO $pdo,$productId,$quantity){
    $quantity=(int)$quantity; if($quantity<1 || $quantity>10000) throw new Exception('Restock quantity must be between 1 and 10,000.');
    $s=$pdo->prepare('UPDATE products SET stock=stock+? WHERE id=?');$s->execute([$quantity,(int)$productId]);
    if(!$s->rowCount()) throw new Exception('Product not found.');
    $s=$pdo->prepare('SELECT name,stock FROM products WHERE id=?');$s->execute([(int)$productId]);return $s->fetch();
}
function create_order(PDO $pdo,$name,$email){
    if(!valid_name($name)) throw new Exception('Please enter a valid name.');
    if(!valid_email($email)) throw new Exception('Please enter a valid email.');
    if(empty($_SESSION['cart'])) throw new Exception('Your cart is empty.');
    $pdo->beginTransaction();
    try{
        $locked=[];$total=0;
        foreach($_SESSION['cart'] as $id=>$qty){$s=$pdo->prepare('SELECT id,name,price,stock FROM products WHERE id=? AND is_active=1 FOR UPDATE');$s->execute([(int)$id]);$p=$s->fetch();$qty=(int)$qty;if(!$p)throw new Exception('A product is no longer available.');if((int)$p['stock']<$qty)throw new Exception('Not enough stock for '.$p['name'].'. Only '.$p['stock'].' left.');$sub=(float)$p['price']*$qty;$total+=$sub;$locked[]=['id'=>$p['id'],'name'=>$p['name'],'price'=>$p['price'],'qty'=>$qty,'subtotal'=>$sub];}
        $s=$pdo->prepare('INSERT INTO orders(user_id,customer_name,customer_email,total) VALUES(?,?,?,?)');$s->execute([$_SESSION['user_id']??null,$name,$email,$total]);$oid=$pdo->lastInsertId();
        $i=$pdo->prepare('INSERT INTO order_items(order_id,product_id,product_name,price,quantity,subtotal) VALUES(?,?,?,?,?,?)');$u=$pdo->prepare('UPDATE products SET stock=stock-? WHERE id=?');
        foreach($locked as $x){$i->execute([$oid,$x['id'],$x['name'],$x['price'],$x['qty'],$x['subtotal']]);$u->execute([$x['qty'],$x['id']]);}
        $pdo->commit();$_SESSION['cart']=[];return $oid;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}

function save_uploaded_product_image($file){
    if(empty($file) || $file['error']===UPLOAD_ERR_NO_FILE) return null;
    if($file['error']!==UPLOAD_ERR_OK) throw new Exception('Image upload failed.');
    if($file['size']>5*1024*1024) throw new Exception('Image must be 5MB or smaller.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!isset($allowed[$mime])) throw new Exception('Only JPG, PNG, and WebP images are allowed.');
    $name=bin2hex(random_bytes(8)).'.'.$allowed[$mime];
    $dir=__DIR__.'/upload/products'; if(!is_dir($dir))mkdir($dir,0755,true);
    if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name)) throw new Exception('Could not save uploaded image.');
    return 'upload/products/'.$name;
}
?>