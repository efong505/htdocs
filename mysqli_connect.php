<?php 

// First database connection (for PHP2MySQL.php - sales table)
DEFINE ('DB_USER', 'root');
DEFINE('DB_PASSWORD', 'Hawaiian2012!');
DEFINE('DB_HOST', '127.0.0.1');
DEFINE ('DB_NAME', 'u75979_85c77b2a7313');

$dbc = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME)
OR die('Could not connect to MySQL: ' .
mysqli_connect_error() );


// Second database connection (for Prophecy.php - author & prophecies tables)
DEFINE ('DB_USER2', 'root');
DEFINE('DB_PASSWORD2', 'Hawaiian2012!');
DEFINE('DB_HOST2', '127.0.0.1');
DEFINE ('DB_NAME2', 'u75979_e33447e1b517');

$dbc2 = @mysqli_connect(DB_HOST2, DB_USER2, DB_PASSWORD2, DB_NAME2)
OR die('Could not connect to MySQL: ' .
mysqli_connect_error() );

?>
