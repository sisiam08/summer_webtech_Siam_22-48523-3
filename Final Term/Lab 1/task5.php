<?php
$word = "Programming";
$vowels = "aeiouAEIOU";
$vowelChars = [];
$consonantChars = [];
for ($i = 0; $i < strlen($word); $i++) {
	if (strpos($vowels, $word[$i]) !== false) {
		$vowelChars[] = $word[$i];
	} elseif (ctype_alpha($word[$i])) {
		$consonantChars[] = $word[$i];
	}
}
echo "Word: $word<br>";
echo "Vowels: ".implode(", ", $vowelChars)."<br>";
echo "Consonants: ".implode(", ", $consonantChars);
?>
