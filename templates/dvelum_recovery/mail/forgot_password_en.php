<?php
$url = "{$this->url}?c={$this->confirmation_code}";
?>

<h2>Hello, <?php echo $this->name; ?>!</h2>

<p>Password recovery url:</p>

<p><a href="<?php echo $url; ?>"><?php echo $url; ?></a></p>

<p>Valid until <?php echo $this->confirmation_date;?></p>