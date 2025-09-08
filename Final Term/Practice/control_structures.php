<?php
// PHP Control Structures - If, Else, Elseif, Switch, Loops

// 1. If-Else Statement
$age = 19;
echo "<h2>If-Else Statement</h2>";
if ($age < 18) {
    echo "You are a minor.<br>";
} elseif ($age == 18) {
    echo "You just became an adult.<br>";
} else {
    echo "You are an adult.<br>";
}

// 2. Switch Statement
$day = date('l'); // Gets the current day name
echo "<h2>Switch Statement</h2>";
echo "Today is $day.<br>";
switch ($day) {
    case 'Monday':
        echo "Start of the work week.<br>";
        break;
    case 'Friday':
        echo "End of the work week.<br>";
        break;
    case 'Saturday':
    case 'Sunday':
        echo "It's the weekend!<br>";
        break;
    default:
        echo "It's a weekday.<br>";
}

// 3. For Loop
echo "<h2>For Loop</h2>";
echo "Counting from 1 to 5:<br>";
for ($i = 1; $i <= 5; $i++) {
    echo "$i ";
}
echo "<br>";

// 4. While Loop
echo "<h2>While Loop</h2>";
echo "Even numbers from 2 to 10:<br>";
$num = 2;
while ($num <= 10) {
    echo "$num ";
    $num += 2;
}
echo "<br>";

// 5. Do-While Loop
echo "<h2>Do-While Loop</h2>";
echo "Counting down from 5 to 1:<br>";
$count = 5;
do {
    echo "$count ";
    $count--;
} while ($count >= 1);
echo "<br>";

// 6. Foreach Loop
echo "<h2>Foreach Loop</h2>";
$colors = ["Red", "Green", "Blue", "Yellow"];
echo "Colors in the array: ";
foreach ($colors as $color) {
    echo "$color ";
}
echo "<br>";

// 7. Foreach with Key => Value
$person = [
    "name" => "John",
    "age" => 25,
    "city" => "New York"
];
echo "<h3>Person Information:</h3>";
foreach ($person as $key => $value) {
    echo ucfirst($key) . ": " . $value . "<br>";
}
?>
