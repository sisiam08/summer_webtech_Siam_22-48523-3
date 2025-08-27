<?php

$name = $email = $website = $comment = $gender = "";
$nameErr = $emailErr = $websiteErr = $genderErr = $fileErr = "";
$profilePicPath = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["name"])) {
        $nameErr = "Name is required";
    } else {
        $name = $_POST["name"];
        if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
            $nameErr = "Only letters and spaces allowed";
        }
    }

    if (empty($_POST["email"])) {
        $emailErr = "Email is required";
    } else {
        $email = $_POST["email"];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        }
    }

    if (!empty($_POST["website"])) {
        $website = $_POST["website"];
        if (!filter_var($website, FILTER_VALIDATE_URL)) {
            $websiteErr = "Invalid URL";
        }
    }

    if (!empty($_POST["comment"])) {
        $comment = $_POST["comment"];
    }

    if (empty($_POST["gender"])) {
        $genderErr = "Gender is required";
    } else {
        $gender = $_POST["gender"];
    }

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] != 4) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($_FILES['profile_pic']['type'], $allowed)) {
            $fileErr = "Only JPG, JPEG, PNG allowed.";
        } else {
            $dir = 'uploads/';
            if (!is_dir($dir)) mkdir($dir);
            $profilePicPath = $dir . basename($_FILES['profile_pic']['name']);
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], $profilePicPath);
        }
    }

    if (!$nameErr && !$emailErr && !$websiteErr && !$genderErr && !$fileErr) {
        echo '<h3 style="color:green;">Form submitted successfully!</h3>';
        echo "<b>Name:</b> $name<br>";
        echo "<b>Email:</b> $email<br>";
        if ($website) echo "<b>Website:</b> $website<br>";
        if ($comment) echo "<b>Comment:</b> " . nl2br($comment) . "<br>";
        echo "<b>Gender:</b> $gender<br>";
        if ($profilePicPath) {
            echo '<b>Profile Picture:</b><br><img src="' . htmlspecialchars($profilePicPath) . '" style="max-width:150px;max-height:150px;"><br>';
        }
    } else {
        echo '<h3 style="color:red;">Please fix the errors below and resubmit the form.</h3>';
        if ($nameErr) echo "<div class='error'>Name: $nameErr</div>";
        if ($emailErr) echo "<div class='error'>Email: $emailErr</div>";
        if ($websiteErr) echo "<div class='error'>Website: $websiteErr</div>";
        if ($genderErr) echo "<div class='error'>Gender: $genderErr</div>";
        if ($fileErr) echo "<div class='error'>Profile Picture: $fileErr</div>";
    }
}
?>
