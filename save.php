<?php
header('Access-Control-Allow-Origin:*');header('Access-Control-Allow-Methods:POST,OPTIONS');header('Access-Control-Allow-Headers:Content-Type');
if($_SERVER['REQUEST_METHOD']==='OPTIONS')exit(0);$d=file_get_contents('php://input');
if($d){file_put_contents('customers.json',$d);file_put_contents('data.json',$d);echo '{"ok":1}';}
?>
