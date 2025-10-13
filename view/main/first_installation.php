<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First Installation</title>
</head>
<style>
    html {
        scroll-behavior: smooth;
    }
</style>
<body>
    <!-- bagian sini -->
    <h1>First Installation</h1>
    <li><a href="#block-1">From Composer</a></li>
    <li><a href="#block-2">Fetch from github</a></li>
    <br>
    <hr />
    <br>
    <b>Before continue, read this:</b><br>
    Hi, my name is Muhammad Mahfudli Asy'ari, thanks for using my framework :)
    <br>but, before continue, this project is under development, it can cause bug or might have unexpected error
    <br><br>
    <b>Installation: </b>
    here for some installation <br><br>

    <div id="block-1">
        <h3>From Composer </h3>
        <div style="background-color: black; color: white; padding: 5px; font-family: Courier;">composer require
            martabakmanis/php-baseroute</div>
        <br>or using the composer.json <br><br>
        <div style="font-family: Courier; padding: 5px; background-color: black; color: white; font-size: 12px;">
            {<br>
            &nbsp;&nbsp;"name": "myproc/main",<br>
            &nbsp;&nbsp;"autoload": {<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"psr-4": {<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "Myproc\\Main\\": "src/"<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>
            &nbsp;&nbsp;},<br>
            &nbsp;&nbsp;"authors": [<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{ <br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"name": "your-naem" <br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;} <br>
            &nbsp;&nbsp;], <br>
            &nbsp;&nbsp;"require": { <br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>"martabakmanis/php-baseroute": "^0.1"</b><br>
            &nbsp;&nbsp;} <br>
            }
        </div>
    </div>

    <div id="block-2">
        <h3>Fetch from github</h3>
        <div style="background-color: black; color: white; padding: 5px; font-family: Courier;">
            <b>git clone</b> https://github.com/Akhmat31/martabakmanis-php-baseroute
        </div>
    </div>
    <br><br>
    <a href="/basic-usages">next to <b>Basic usages ==></b></a>
</body>
</html>