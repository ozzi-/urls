# urls - PHP URL shortener
This is a fast and bare bones URL shortener to host on your own. It takes 2 minutes to set up.

No database is required, a json file is used for persistence, make sure it is not served by your webserver directly (see .htaccess).
Login credentials are stroed in the PHP file itself, do not forget to change them.

It will create short links containing a-zA-Z0-9 and of 5 characters length.
Example shortened link:
https://yourpage.com/?UdOJK

Using https://yourpage.com/?admin will allow you to log in and manage your short links (list, add, delete).
