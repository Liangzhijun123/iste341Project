<?php
echo "<h3>Copy these hashes:</h3>";

echo "<strong>adminpassword</strong> hash:<br>";
echo password_hash("adminpassword", PASSWORD_DEFAULT);

echo "<br><br>";

echo "<strong>managerpassword</strong> hash:<br>";
echo password_hash("managerpassword", PASSWORD_DEFAULT);
?>