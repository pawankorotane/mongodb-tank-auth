mongodb-tank-auth
=================

 MongoDb Tank Auth Library for Codeigniter

MongoDB for Tank Auth is an authentication library for CI using MongoDB as a storage engine. Tank Auth is very secure library for CI I used in almost all my project I have started a new project I was looking for authentication library in mongodb but I couldn’t found good one so I decide to modify Tank Auth to use mongoDB I wrote this library in very small time scale if you find any bug please let me know.

External Libraries used in Tank Auth
1.	To know about tank auth visit http://konyukhov.com/soft/tank_auth/
2.	This is MongoDB driver for CI  https://github.com/alexbilbie/codeigniter-mongodb-library
3.	For CI session database storage  https://github.com/sepehr/ci-mongodb-session
Installation:
1.	Download the application folder extract it copy all the files in the folder into your CI projects.
2.	Add the MongoDB connection details in “application/config/mongodb.php”
3.	I have auto loaded the MongoDB driver in CI “application/config/autoload.php”
4.	Add SMTP settings in “application/config/tank_auth.php” there are other config settings as well you can set those depends on you requirement.
5.	That’s it.

