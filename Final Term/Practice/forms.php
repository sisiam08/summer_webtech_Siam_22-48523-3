<?php
    // Sanitaization
    function test_input($data)
    {
       $data = trim($data);
       $data = stripslashes($data);
       $data = htmlspecialchars($data);

       return $data;
    }




    //  validation
    $name = $email = $age = "";
    $nameErr = $emailErr = $ageErr = "";

    if($_SERVER["REQUEST_METHOD"]=="POST")
    {
        if(empty($_POST['name']))
        {
            $nameErr = "Name is required!";
        }
        else
        {
            $name = test_input($_POST['name']);
        }

        if(empty($_POST['email']))
        {
            $emailErr = "Email is required!";
        }
        elseif(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
        {
            $emailErr = "Invalid email format!";
        }
        else
        {
            $email = test_input($_POST['email']);
        }

        if(empty($_POST['age']))
        {
            $ageErr = "Age is required!";
        }
        elseif(!is_numeric($_POST["age"]))
        {
            $ageErr = "Only numbers are allowed!";
        }
        else
        {
            $age = test_input($_POST['age']);
        }
    }

    if(empty($nameErr) && empty($emailErr) && empty($ageErr))
    {
        echo "My name is $name and I am $age years old.";
        echo "<br>";
        echo "My email is $email.";
    }
    else
    {
        echo $nameErr . "<br>";
        echo $emailErr . "<br>";
        echo $ageErr . "<br>";
    }
?>