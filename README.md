mongodb-tank-auth
=================

 MongoDb Tank Auth Library for Codeigniter

<p>MongoDB for Tank Auth is an authentication library for CI using MongoDB as a storage engine. Tank Auth is very secure library for CI I used in almost all my project I have started a new project I was looking for authentication library in mongodb but I couldn’t found good one so I decide to modify Tank Auth to use mongoDB I wrote this library in very small time scale if you find any bug please let me know.</p>

<h2>External Libraries used in Tank Auth</h2>
<ol>
<li>To know about tank auth visit http://konyukhov.com/soft/tank_auth/</li>
<li>This is MongoDB driver for CI  https://github.com/alexbilbie/codeigniter-mongodb-library</li>
<li>For CI session database storage  https://github.com/sepehr/ci-mongodb-session</li>
</ol>
<h2>Installation:</h2>
<ol>
<li>Download the application folder extract it copy all the files in the folder into your CI projects.</li>
<li>Add the MongoDB connection details in “application/config/mongodb.php”. </li>
<li>I have auto loaded the MongoDB driver in CI “application/config/autoload.php”. </li>
<li>Add SMTP settings in “application/config/tank_auth.php” there are other config settings as well you can set those depends on you requirement. </li>
<li>Create folder name "captcha" in your project root.</li>
<li>That’s it.
</ol>

