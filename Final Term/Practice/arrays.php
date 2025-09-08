<?php
// PHP Arrays and Array Functions

// 1. Indexed Arrays
echo "<h2>Indexed Arrays</h2>";
$fruits = ["Apple", "Banana", "Orange", "Mango"];
echo "Fruits: " . implode(", ", $fruits) . "<br>";
echo "First fruit: " . $fruits[0] . "<br>";
echo "Total fruits: " . count($fruits) . "<br>";

// 2. Associative Arrays
echo "<h2>Associative Arrays</h2>";
$student = [
    "name" => "Ali",
    "id" => "23-12345-6",
    "cgpa" => 3.75,
    "department" => "CSE"
];
echo "Student ID: " . $student["id"] . "<br>";
echo "Student Name: " . $student["name"] . "<br>";

// 3. Multidimensional Arrays
echo "<h2>Multidimensional Arrays</h2>";
$students = [
    ["Ali", "23-12345-6", 3.75],
    ["Siam", "23-12346-7", 3.85],
    ["Rafi", "23-12347-8", 3.95]
];
echo "Second student's name: " . $students[1][0] . "<br>";
echo "Third student's CGPA: " . $students[2][2] . "<br>";

// More complex multidimensional array
$courses = [
    "CSE" => [
        "CSE110" => ["Web Technology", 3],
        "CSE111" => ["Database", 3]
    ],
    "EEE" => [
        "EEE201" => ["Circuit Analysis", 4],
        "EEE202" => ["Electronics", 3]
    ]
];
echo "Web Technology credit: " . $courses["CSE"]["CSE110"][1] . "<br>";

// 4. Array Functions
echo "<h2>Array Functions</h2>";

// Adding elements
$numbers = [1, 2, 3];
array_push($numbers, 4, 5); // Add to end
array_unshift($numbers, 0); // Add to beginning
echo "After adding elements: " . implode(", ", $numbers) . "<br>";

// Removing elements
$stack = ["apple", "orange", "banana", "peach"];
$last = array_pop($stack); // Remove from end
$first = array_shift($stack); // Remove from beginning
echo "Removed first: $first, Removed last: $last<br>";
echo "Remaining: " . implode(", ", $stack) . "<br>";

// Sorting arrays
$names = ["Zara", "Ali", "Xaio", "David"];
sort($names); // Sort alphabetically
echo "Sorted names: " . implode(", ", $names) . "<br>";

$scores = [
    "Ali" => 85,
    "Zara" => 92,
    "David" => 78
];
asort($scores); // Sort by value
echo "Scores sorted by value: <br>";
foreach($scores as $name => $score) {
    echo "$name: $score<br>";
}

ksort($scores); // Sort by key
echo "Scores sorted by name: <br>";
foreach($scores as $name => $score) {
    echo "$name: $score<br>";
}

// Array filtering and mapping
$allNumbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
$evenNumbers = array_filter($allNumbers, fn($n) => $n % 2 == 0);
echo "Even numbers: " . implode(", ", $evenNumbers) . "<br>";

$doubled = array_map(fn($n) => $n * 2, $allNumbers);
echo "Doubled numbers: " . implode(", ", $doubled) . "<br>";

// Array merging
$array1 = ["red", "green"];
$array2 = ["blue", "yellow"];
$mergedArray = array_merge($array1, $array2);
echo "Merged array: " . implode(", ", $mergedArray) . "<br>";

// Array searching
$haystack = ["apple", "banana", "orange", "grape", "apple"];
$position = array_search("orange", $haystack);
echo "Position of 'orange': $position<br>";

$occurrences = array_count_values($haystack);
echo "Number of 'apple' occurrences: " . $occurrences["apple"] . "<br>";
?>
