<title>Register</title>
<h1>Register</h1>

<p> Please enter username, password, name, and email.</a> </p>

<style type="text/css">
	label {
    display: inline-block;
    width:80px;
    text-align: left;
    margin-bottom: 7px;
	}
</style>

<form action="register.php" method="POST">
	<label>Username:</label>
	<input type="text" name="username" required value="<?php echo htmlspecialchars(isset($_POST['username']) ? $_POST['username'] : null); ?>"/><br>
	<label>Password:</label>
	<input type="password" name="password" required/><br>
	<label>Name:</label>
	<input type="text" name="name" required value="<?php echo htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : null); ?>"/><br>
	<label>E-mail:</label>
	<input type="email" name="email" required value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : null); ?>"/><br>
	<input type="submit"/>
</form>

<?php
	if($_SERVER['REQUEST_METHOD'] == 'POST'){
		$username = htmlspecialchars($_POST['username']);
		$password = htmlspecialchars($_POST['password']);
		$name = htmlspecialchars($_POST['name']);
		$email = htmlspecialchars($_POST['email']);

	if(strlen($username) > 16){
	 	echo 'Username must be less than 20 characters.'."<br>";
	}

	if(strlen($password]) < 8 || strlen($password]) >20){
		echo 'Password must be greater than 8 characters or less than 20 characters.';
	}
	
?>