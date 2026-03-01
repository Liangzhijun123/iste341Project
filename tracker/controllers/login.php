<?php
session_start();
require_once __DIR__ . "/../classes/User.class.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $user = new User();
    $data = $user->login($username, $password);

    if ($data) {
        $_SESSION["userId"] = $data["Id"];
        $_SESSION["roleId"] = $data["RoleID"];
        $_SESSION["projectId"] = $data["ProjectId"];
        header("Location: ../views/dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>