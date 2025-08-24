<?php
$arr = [12, 45, 23, 67, 34, 89, 67];
rsort($arr);
$secondMax = null;
for ($i = 1; $i < count($arr); $i++) {
	if ($arr[$i] != $arr[0]) {
		$secondMax = $arr[$i];
		break;
	}
}
echo "Array elements: ".implode(", ", $arr)."<br>";
if ($secondMax !== null) {
	echo "Second maximum number: $secondMax";
} else {
	echo "No second maximum found.";
}
?>
