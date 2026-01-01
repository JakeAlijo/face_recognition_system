<?php 

if(!isset( $_SESSION["name"]) && !isset($_SESSION['phone']) && !isset($_SESSION["mail"]) && !isset($_SESSION["id"]) && !isset($_SESSION["pass"])){

 	echo "<script>window.location.href='index.php'</script>";

}