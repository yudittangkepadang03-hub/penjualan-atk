<?php session_start(); ?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h2>Login</h2>

    <!-- NOTIF ERROR -->
    <?php if (isset($_SESSION['error'])) { ?>
        <div class="error">
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
        </div>
    <?php } ?>

    <form method="POST" action="login.php">
        <input type="email" name="email" placeholder="Email"
            value="<?php echo isset($_SESSION['old_email']) ? $_SESSION['old_email'] : ''; ?>" required>

        <input type="password" name="password" placeholder="Password" required>

        <button type="submit" name="login">Masuk</button>
    </form>

    <a href="form_register.php">Belum punya akun? Daftar</a>
</div>

</body>
</html>