<form action="" method="post">
    username: <input type="text" name="username" placeholder="Enter username"><br><br>
    password: <input type="text" name="password" placeholder="Enter password"><br><br>
    <input type="submit" value="Set Session" name="setSession">
    <input type="submit" value="Display Session" name="displaySession">
    <input type="submit" value="Delete Session" name="deleteSession">
</form>


<?php
if($_SERVER["REQUEST_METHOD"]=="POST")
{
    if(isset($_POST['setSession']))
    {
        session_start();
        $username = $_POST['username'];
        $password = $_POST['password'];

        $_SESSION['username']=$username;
        $_SESSION['password']=$password;
        echo "Session set successfully.";
    }
    elseif(isset($_POST['displaySession']))
    {
        session_start();
        if(isset($_SESSION['username']) && isset($_SESSION['password']))
        {
            echo "Username: " . $_SESSION["username"];
            echo "<br>";
            echo "Password: ". $_SESSION['password'];
        }
        else
        {
            echo "No session data found.";
        }
    }
    elseif(isset($_POST['deleteSession']))
    {
        session_start();
        session_destroy();
        echo "Session deleted successfully.";
    }
}
?>