<?php
session_start();

if (isset($_SESSION['email'])) {
    header('X-User-Email: ' . $_SESSION['email']);
}
?>