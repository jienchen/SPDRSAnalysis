<title>ETF Information Compiler Login</title>
<h1>ETF Information Compiler</h1>

<?php
?>

<p> Please log in or <a href='register.php'>register</a> </p>

<style type="text/css">
	label {
    display: inline-block;
    width:80px;
    text-align: left;
    margin-bottom: 7px;
	}
</style>

<form action="processing/user.php" method="POST">
	<label>Username:</label>
	<input type="text" name="username" required/><br>
	<label>Password:</label>
	<input type="password" name="password" required/><br>
	<input type="submit" mame="submit" value="login"/>
</form>
