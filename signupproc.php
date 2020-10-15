<?php

$theuserpath = $_POST["theusername"];

$servername = "localhost";
$username = "root";
$password = "Ftf51678@1";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$thedb = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(1,10))), 1, 10);

$sql = "CREATE DATABASE $thedb";
$cookie_name = "thedb";
$cookie_value = $thedb;
setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");
if ($conn->query($sql) === TRUE) {
$src = "/home/supportonsite/public_html/maincopy";
$dest = "/home/supportonsite/public_html/" . $theuserpath;
shell_exec("cp -r $src $dest");
$file = '/home/supportonsite/public_html/' . $theuserpath . '/js/init.js';
$file_contents = file_get_contents($file);

$fh = fopen($file, "w");
$file_contents = str_replace('theadmin',$theuserpath,$file_contents);
fwrite($fh, $file_contents);
fclose($fh);
    header("Location: https://supportonsite.co/$theuserpath");
} else {
    
}

$conn->close();
?>