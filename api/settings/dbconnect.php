<?php

function connect_db()
{
    $host = "localhost";
    $user = "root";
    $pass = "root";
    $db_name = "vaibhavn_api";
    try
    {
        $connection = new PDO("mysql:host=$host;dbname=$db_name", $user, $pass);
        // set the PDO error mode to exception
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $connection;
    }
    catch(PDOException $e)
    {
        echo "Connection failed: " . $e->getMessage();
    }
}