<?php
$name = "Ali";
$age = 25;
echo "My name is $name. <br> I am $age years old.";
echo "<br>";

$x=10;
$y=20;
echo $x+$y; 

$mul = $x*$y;
echo "<br> $mul";
echo "<br>";
?>


<?php
  $name = "Siam";      // String
  $age = 23;           // Integer
  $height = 5.9;       // Float
  $isStudent = true;   // Boolean
  $friends = ["A", "B", "C"]; // Array
  $nothing = NULL;     // Null

  var_dump($name);
  var_dump($age);
  var_dump($height);
  var_dump($isStudent);
  var_dump($friends);
  var_dump($nothing);





  echo "<br> <br> <br>";



  $email = "student@example.com";

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      echo "Invalid email format!";
  } else {
      echo "Valid email: $email";
  }


?>
