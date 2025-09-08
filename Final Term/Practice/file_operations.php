<?php
// PHP File Operations

// Function to display messages with styling
function displayMessage($message, $type = 'info') {
    $color = 'black';
    switch ($type) {
        case 'success': $color = 'green'; break;
        case 'error': $color = 'red'; break;
        case 'warning': $color = 'orange'; break;
        case 'info': $color = 'blue'; break;
    }
    echo "<p style='color: $color; margin: 5px 0;'>$message</p>";
}

// Create a test directory if it doesn't exist
$testDir = __DIR__ . '/test_files';
if (!file_exists($testDir)) {
    if (mkdir($testDir, 0777, true)) {
        displayMessage("Created test directory: test_files", 'success');
    } else {
        displayMessage("Failed to create test directory", 'error');
    }
} else {
    displayMessage("Test directory already exists", 'info');
}

// File path for examples
$filePath = $testDir . '/sample.txt';
$csvFilePath = $testDir . '/data.csv';
$jsonFilePath = $testDir . '/data.json';

// Create/Write to a file
$content = "This is a sample text file.\nCreated for PHP file operations demo.\nLine 3 of the file.";
if (file_put_contents($filePath, $content)) {
    displayMessage("Successfully wrote to file: sample.txt", 'success');
} else {
    displayMessage("Failed to write to file", 'error');
}

// Read from a file
if (file_exists($filePath)) {
    // Read entire file
    $fileContent = file_get_contents($filePath);
    displayMessage("File contents read successfully", 'success');
    
    // Read file line by line
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    displayMessage("Read " . count($lines) . " lines from the file", 'info');
} else {
    displayMessage("File does not exist", 'error');
}

// Append to a file
$additionalContent = "\nThis line was appended.";
if (file_put_contents($filePath, $additionalContent, FILE_APPEND)) {
    displayMessage("Successfully appended to file", 'success');
} else {
    displayMessage("Failed to append to file", 'error');
}

// Create CSV file
$csvData = [
    ['Name', 'Age', 'City'],
    ['John', 25, 'New York'],
    ['Jane', 22, 'Boston'],
    ['Mike', 30, 'Chicago']
];

$csvFile = fopen($csvFilePath, 'w');
if ($csvFile) {
    foreach ($csvData as $row) {
        fputcsv($csvFile, $row);
    }
    fclose($csvFile);
    displayMessage("CSV file created successfully: data.csv", 'success');
} else {
    displayMessage("Failed to create CSV file", 'error');
}

// Create JSON file
$jsonData = [
    'students' => [
        [
            'name' => 'John',
            'age' => 25,
            'courses' => ['Math', 'Physics']
        ],
        [
            'name' => 'Jane',
            'age' => 22,
            'courses' => ['Chemistry', 'Biology']
        ]
    ]
];

if (file_put_contents($jsonFilePath, json_encode($jsonData, JSON_PRETTY_PRINT))) {
    displayMessage("JSON file created successfully: data.json", 'success');
} else {
    displayMessage("Failed to create JSON file", 'error');
}

// File information
if (file_exists($filePath)) {
    displayMessage("File Information:", 'info');
    displayMessage("File size: " . filesize($filePath) . " bytes", 'info');
    displayMessage("Last modified: " . date("F d Y H:i:s", filemtime($filePath)), 'info');
    displayMessage("File type: " . filetype($filePath), 'info');
    displayMessage("Is readable: " . (is_readable($filePath) ? 'Yes' : 'No'), 'info');
    displayMessage("Is writable: " . (is_writable($filePath) ? 'Yes' : 'No'), 'info');
}

// Copy a file
$copyPath = $testDir . '/sample_copy.txt';
if (copy($filePath, $copyPath)) {
    displayMessage("File copied successfully to sample_copy.txt", 'success');
} else {
    displayMessage("Failed to copy file", 'error');
}

// Rename a file
$renamePath = $testDir . '/sample_renamed.txt';
if (file_exists($renamePath)) {
    unlink($renamePath); // Delete if already exists
}

if (rename($copyPath, $renamePath)) {
    displayMessage("File renamed from sample_copy.txt to sample_renamed.txt", 'success');
} else {
    displayMessage("Failed to rename file", 'error');
}

// Delete a file
if (file_exists($renamePath) && unlink($renamePath)) {
    displayMessage("File sample_renamed.txt deleted successfully", 'success');
} else {
    displayMessage("Failed to delete file or file doesn't exist", 'error');
}

// Directory operations
$subdirPath = $testDir . '/subdir';
if (!file_exists($subdirPath) && mkdir($subdirPath)) {
    displayMessage("Subdirectory created: test_files/subdir", 'success');
} else {
    displayMessage("Subdirectory already exists or failed to create", 'info');
}

// List files in a directory
$files = scandir($testDir);
displayMessage("Files in test_files directory:", 'info');
echo "<ul>";
foreach ($files as $file) {
    if ($file != "." && $file != "..") {
        echo "<li>$file</li>";
    }
}
echo "</ul>";

// Remove directory (only if empty)
if (file_exists($subdirPath) && is_dir($subdirPath)) {
    if (rmdir($subdirPath)) {
        displayMessage("Subdirectory removed successfully", 'success');
    } else {
        displayMessage("Failed to remove subdirectory (may not be empty)", 'error');
    }
}

// File locking example
$lockFilePath = $testDir . '/lock_example.txt';
$lockFile = fopen($lockFilePath, 'w');
if ($lockFile) {
    if (flock($lockFile, LOCK_EX)) { // Exclusive lock
        fwrite($lockFile, "This file was locked during writing to prevent concurrent access.\n");
        fflush($lockFile); // Flush output before releasing the lock
        flock($lockFile, LOCK_UN); // Release the lock
        displayMessage("File was locked, written to, and unlocked", 'success');
    } else {
        displayMessage("Could not get lock on file", 'error');
    }
    fclose($lockFile);
} else {
    displayMessage("Could not open file for locking example", 'error');
}

// File upload form
echo '<h2>File Upload Form Example</h2>';
echo '<form action="" method="post" enctype="multipart/form-data">
    <label for="fileToUpload">Select file to upload:</label>
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="Upload File" name="submit">
</form>';

// Handle file upload
if (isset($_POST["submit"])) {
    $targetFile = $testDir . '/' . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if file already exists
    if (file_exists($targetFile)) {
        displayMessage("File already exists.", 'error');
        $uploadOk = 0;
    }
    
    // Check file size (limit to 500KB)
    if ($_FILES["fileToUpload"]["size"] > 500000) {
        displayMessage("File is too large.", 'error');
        $uploadOk = 0;
    }
    
    // Allow certain file formats
    if($fileType != "txt" && $fileType != "pdf" && $fileType != "doc" && $fileType != "docx") {
        displayMessage("Only TXT, PDF, DOC & DOCX files are allowed.", 'error');
        $uploadOk = 0;
    }
    
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        displayMessage("Your file was not uploaded.", 'error');
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {
            displayMessage("The file ". htmlspecialchars(basename($_FILES["fileToUpload"]["name"])). " has been uploaded.", 'success');
        } else {
            displayMessage("There was an error uploading your file.", 'error');
        }
    }
}

// Show file contents from our example files
echo "<h2>Sample File Content</h2>";
if (file_exists($filePath)) {
    echo "<h3>Text File (sample.txt):</h3>";
    echo "<pre>" . htmlspecialchars(file_get_contents($filePath)) . "</pre>";
}

if (file_exists($csvFilePath)) {
    echo "<h3>CSV File (data.csv):</h3>";
    echo "<table border='1'>";
    $csvFile = fopen($csvFilePath, 'r');
    while (($row = fgetcsv($csvFile)) !== FALSE) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
    fclose($csvFile);
    echo "</table>";
}

if (file_exists($jsonFilePath)) {
    echo "<h3>JSON File (data.json):</h3>";
    echo "<pre>" . htmlspecialchars(file_get_contents($jsonFilePath)) . "</pre>";
    
    // Parse and display JSON
    $jsonContent = json_decode(file_get_contents($jsonFilePath), true);
    echo "<h4>Parsed JSON:</h4>";
    echo "<ul>";
    foreach ($jsonContent['students'] as $student) {
        echo "<li>Name: " . $student['name'] . ", Age: " . $student['age'] . ", Courses: " . implode(", ", $student['courses']) . "</li>";
    }
    echo "</ul>";
}
?>
