<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h2>Registrasi</h2>
    <form method="POST" action="register.php">
        <input type="text" name="nama" placeholder="Nama Lengkap" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="register">Daftar</button>
    </form>

    <a href="form_login.php">Sudah punya akun? Login</a>
</div>

</body>
</html>