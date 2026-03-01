<?php
// hash.php
$password = "user1password";  // the real password you want
echo password_hash($password, PASSWORD_DEFAULT);