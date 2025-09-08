<?php
// PHP Functions - Declaration, Parameters, Return Values, Scope

// 1. Basic Function
echo "<h2>Basic Function</h2>";
function sayHello() {
    echo "Hello, World!<br>";
}
sayHello(); // Calling the function

// 2. Function with Parameters
echo "<h2>Function with Parameters</h2>";
function greet($name) {
    echo "Hello, $name!<br>";
}
greet("Sarah");
greet("Michael");

// 3. Function with Default Parameter Values
echo "<h2>Function with Default Parameters</h2>";
function displayInfo($name, $course = "Web Technology") {
    echo "$name is studying $course.<br>";
}
displayInfo("Ali");
displayInfo("Siam", "Database");

// 4. Function with Return Value
echo "<h2>Function with Return Value</h2>";
function addNumbers($a, $b) {
    return $a + $b;
}
$result = addNumbers(5, 10);
echo "5 + 10 = $result<br>";

// 5. Function with Multiple Return Values (using array)
echo "<h2>Function with Multiple Return Values</h2>";
function getPersonInfo() {
    $name = "John";
    $age = 25;
    $city = "New York";
    return [$name, $age, $city];
}
list($name, $age, $city) = getPersonInfo();
echo "Name: $name, Age: $age, City: $city<br>";

// 6. Variable Scope
echo "<h2>Variable Scope</h2>";
$globalVar = "I'm a global variable";

function testScope() {
    $localVar = "I'm a local variable";
    
    // Access global variable inside function
    global $globalVar;
    echo "Inside function: $globalVar<br>";
    echo "Inside function: $localVar<br>";
}

testScope();
echo "Outside function: $globalVar<br>";
// echo "Outside function: $localVar"; // This would cause an error

// 7. Anonymous Functions (Lambda)
echo "<h2>Anonymous Functions</h2>";
$multiply = function($a, $b) {
    return $a * $b;
};
echo "5 × 4 = " . $multiply(5, 4) . "<br>";

// 8. Arrow Functions (PHP 7.4+)
echo "<h2>Arrow Functions (PHP 7.4+)</h2>";
$numbers = [1, 2, 3, 4, 5];
$squared = array_map(fn($n) => $n * $n, $numbers);
echo "Original numbers: " . implode(", ", $numbers) . "<br>";
echo "Squared numbers: " . implode(", ", $squared) . "<br>";

// 9. Recursive Functions
echo "<h2>Recursive Functions</h2>";
function factorial($n) {
    if ($n <= 1) {
        return 1;
    } else {
        return $n * factorial($n - 1);
    }
}
echo "Factorial of 5 = " . factorial(5) . "<br>";
?>
