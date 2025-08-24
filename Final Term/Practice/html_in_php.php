<?php
$h2_tag='blue';
echo "<h1 style='color:blue'>html in php</h1>";
echo "<h3 style='color:red'>html in php</h3>";

$name = "Ali";
echo "<h2 style='color:gray'>My name is $name</h2>";
?>


<h1>
    Hello, This is <?php echo "$name."; ?>
</h1>


<h2 style='color:<?php echo $h2_tag?>'>php in html tag</h2>
<h2 style='color:<?php echo $h2_tag?>'>My name is <?php echo $name;?></h2>