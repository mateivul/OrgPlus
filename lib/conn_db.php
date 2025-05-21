<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'db_name';

$mysqli = new mysqli($servername, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

require 'user_fct.php';
require 'security.php';
