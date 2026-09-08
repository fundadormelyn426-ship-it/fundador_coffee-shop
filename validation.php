<?php
function valid_name($name){ return (bool)preg_match("/^[\p{L} .'-]{2,100}$/u", trim($name)); }
function valid_email($email){ return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false; }
function valid_password($password){ return is_string($password) && strlen($password) >= 6; }
function require_post($fields){ foreach($fields as $f){ if(!isset($_POST[$f]) || trim((string)$_POST[$f])==='') return false; } return true; }
function clean_text($value,$max=255){ return mb_substr(trim((string)$value),0,$max); }
?>