<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test</title>
</head>
<style>
    .menu-tree {
        font-size: 15px;
        padding: 10px;
        max-width: 260px;
    }

    .menu-tree>a {
        display: block;
        font-weight: 600;
        text-decoration: none;
        color: blue;
        cursor: pointer;
        padding: 6px 8px;
        border-radius: 6px;
        transition: background 0.2s ease;
    }

    .menu-tree>a:hover {
        background: #e6f0ff;
    }

    .menu-tree ul {
        list-style: none;
        margin: 6px 0 0 12px;
        padding: 0;
        border-left: 2px solid #000;
    }

    .menu-tree li {
        padding: 4px 0 4px 10px;
        position: relative;
        cursor: pointer;
        transition: color 0.2s ease;
    }

    .menu-tree li::before {
        content: "•";
        display: none;
        color: #000;
        position: absolute;
        left: -12px;
    }

    .menu-tree li:hover {
        color: blue;
    }
</style>

<body>
    <h1>Basic usages</h1>
    This page for basic usage of my framework, here list for basic usage: <br>
    <div class="menu-tree">
        <a href="#">Basic usages</a>
        <ul>
            <li>Basic route definition</li>
            <li>View function</li>
            <li>get or post chaining</li>
        </ul>
    </div>

</body>

</html>