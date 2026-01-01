<?php 
session_start();
include('admin/config.php');

if(isset($_POST['login'])){
  $uEmail = Sanitize_Data($_POST['contact']);
  $uPass = Sanitize_Data($_POST['password']);

  if ($uEmail =="" || $uPass =="" ) {
      echo '<script>alert("All fields are required")</script>';
       echo "<script>window.location.href='../logout.php'</script>";
              
    }else{
  
           $validateUser_sql = "SELECT * FROM user WHERE contact = '$uEmail' && password = '$uPass'";
  
           $validateUser_exe = mysqli_query($conn, $validateUser_sql);
  
           if($validateUser_exe){
  
              if(mysqli_num_rows($validateUser_exe) == 0){
    
                echo '<script>alert("Invalid Email and/or Password")</script>';
                echo "<script>window.location.href='index.php'</script>";
    
              }else{
    
                while($validateUser_row = mysqli_fetch_array($validateUser_exe)){
    
                  $sch_id = $validateUser_row["password"];
                  $fname = $validateUser_row["name"];
                  $phone = $validateUser_row["contact"];
                   $pass = $validateUser_row["userID"];
                   $role = $validateUser_row["role"];
                   $position = $validateUser_row["position"];
                
                }

                     $_SESSION["role"] = $role;
                    $_SESSION['ids'] = $sch_id;
                     $_SESSION["phone"] = $phone;
                    $_SESSION["name"] = $fname;
                     $_SESSION["datee"] = $pass;
                     $_SESSION["position"] = $position;
                   
      
                   
                     switch($role){
                       case "admin":
                           echo "<script>window.location.href='admin/'</script>";
                        break;

                        case "student":
                             echo "<script>window.location.href='attendance/'</script>";
                          break;


                          default:
                          echo "<script>window.location.href='attendance/'</script>";
                          break;
                     }
                 
    
                
    
    
             
              }
    
            }
    }
}


?>