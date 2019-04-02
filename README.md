# urls - PHP URL shortener
This is a fast and bare bones URL shortener to host on your own. It takes 2 minutes to set up.

No database is required, a json file is used for persistence.

It will create short links containing a-zA-Z0-9 and of 5 characters length.
Example shortened link:
hxxps://yourpage.com/?UdOJK

Using hxxps://yourpage.com/?admin will allow you to log in and manage your short links (list, add, delete).
The admin interface can be additionally protected by IP whitelisting. 

You can create short links programatically through the API (see below).

## Demo
You can find a demo instance here, login with username "shortener" and "letmein" as a password:
https://oz-web.com/urls/?admin

## Installation
0. Clone repo
1. Check that your webserver respects the .htaccess file and does not serve db.json (otherwise you will leak all shortlinks)
2. Edit index.php and change the username, password and edit the IP whitelist to your liking
3. Navigate to yourwebserver.com/path?admin and login

## Screenshots
Management Interface:
![management interface](https://i.imgur.com/5ENbtvB.png)

Login:
![management interface](https://i.imgur.com/0rgOnXe.png)

## API
Shorten a new URL to be shortened:
```
curl -X POST http://demo.local.ch/urls/index.php -d "api=addurl&url=http://example.com&usr=shortener&pwd=letmein"

{"code":"CiuZ"}
```

List all shortened URLs:
```
curl -X POST http://demo.local.churls/index.php -d "api=listurls&usr=shortener&pwd=letmein"
{"cs5G":"http://example2.com","8TRL":"http://somewebsite.ch","e2XK":"http://github.com","CiuZ":"http://example.com"}
```

Delete shortened URLs:
```
curl -X POST http://demo.local.ch/urls/index.php -d "api=delurl&code=CiuZ&usr=shortener&pwd=letmein"
{"success":true}
```

Get base path:
```
 curl -X POST http://demo.local.ch/urls/index.php -d "api=getpath&usr=shortener&pwd=letmein"
{"path":"http://demo.local.ch/urls"}
```
