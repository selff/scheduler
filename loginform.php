<?php
/**
 * Created by PhpStorm.
 * User: andreyselikov
 * Date: 09.10.2018
 * Time: 20:29
 */

include_once('./templates/header.html');
?>

    <form class="form-signin" method="POST" action="index.php">
        <h2 class="form-signin-heading">Please sign in</h2>
        <label for="inputEmail" class="sr-only">Login</label>
        <input type="login" name="login" id="inputLogin" class="form-control" placeholder="Login" required autofocus>
        <label for="inputPassword" class="sr-only">Password</label>
        <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Password" required>
        <div class="checkbox">
            <label>
                <input type="checkbox" value="remember-me"> Remember me
            </label>
        </div>
        <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
    </form>

<?php
include_once('./templates/footer.html');