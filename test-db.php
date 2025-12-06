<?php

require_once __DIR__ . '/core/database.php';

$db = Database::getInstance();

var_dump($db);

//object(PDO)#1 (0) { } #If connected successfully

/*catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
} #If there's an error*/

