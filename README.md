# REST API for cloud storage web-application

## README.md

* en [English](README.md)
* ru [Русский](./readme/README.ru.md)

## Table of contents

* [Technologies used in the project](#technologies-used-in-the-project)
* [Project setup](#project-setup)
* [Importing database](#importing-database)
* [Configuration](#configuration)
* [Setting up Apache](#setting-up-apache)
* [Server endpoints](#server-endpoints)
  * [Notes on data](#notes-on-data)
  * [Users](#users)
  * [Admin](#admin)
  * [Directories](#directories)
  * [Files](#files)
  * [Share](#share)
* [Postman commands](#postman-commands)
* [Notes](#notes)

## Technologies used in the project

* `PHP`
* `PHPStan`

## Project setup

Steps to set up the project:

* Import database
* Create configuration files
* Set up Apache

## Importing database

To import the database, run this command

```bash
mysql -u username -p [Database name] < "./project_files/database_dump.sql"
```

## Configuration

Create config files with data for SMTP and MySQL in the `./src/Config/Secret/`
folder.
Examples of configuration are inside `./src/Config/SecretExample/`.

## Starting the development server

You can set up Apache, or you can run the bash script `./devServer.sh` to run the
development server.

## Setting up Apache

The configuration file for your virtual server should look like this
(default folder is `/var/www/html`):

```conf
<VirtualHost *:80>
  DocumentRoot /var/www/html
  ServerName cloud-storage.local
  ServerAlias www.cloud-storage.local
  ErrorLog "/var/log/apache2/cloud-storage.local-error.log"
  CustomLog "/var/log/apache2/cloud-storage.local-access.log"
  common
  <Directory /var/www/html/>
    Options +Indexes +Includes +FollowSymLinks +MultiViews
    AllowOverride All
    Require all granted
    <IfModule mod_rewrite.c>
      Options -MultiViews
      RewriteEngine On
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteRule ^(.*)$ /index.php [QSA,L]
    </IfModule>
  </Directory>
</VirtualHost>
```

Make sure that `apache_mod_rewrite` is enabled.

## Server endpoints

### Notes on data

`dob`, `gender` can be `null`. `lastName` can be empty string.
`gender` can be either "M" or "F".

If `parentId` is received as `null` it is considered a root directory.

### Users

`GET: /users/list` - Get a list of users with safe information

`GET: /users/get/{userId}` - Get the safe information about a specific user

`GET: /users/search/{email}` - Search users by email

Return data:

```json
{
  "id": 18,
  "firstName": "John",
  "lastName": "Smith",
  "email": "user@example.com",
  "dob": {
    "date": "1997-06-25 00:00:00.000000",
    "timezone_type": 3,
    "timezone": "UTC"
  },
  "gender": "M"
}
```

`PUT: /users/update` - Change current user info

Receives:

```json
{
    "firstName": "John",
    "lastName": "Smith",
    "dob": "1997-06-25",
    "gender": "M"
}
```

`POST: /users/login` - Log into account

Receives:

```json
{
    "email": "user@example.com",
    "password": "password"
}
```

`GET: /users/reset_password?email=username@example.com` - Send reset token
to the user's mail

Receives: Query parameter `email`

`POST: /users/reset_password` - Reset password

Receives:

```json
{
    "email": "user@example.com",
    "password": "password",
    "token": "P+axG5k9gStneqbLmH9K4e3u8DIv0Bdh6iPUDu4zFbxuyd7GjuUBWXPc/x8eTgb+Mh75WuTF41jlu5Qh"
}
```

`POST: /users/register` - Register

Receives:

```json
{
    "firstName": "John",
    "lastName": "Smith",
    "email":"user1@example.com",
    "password": "password",
    "dob": "1994-06-25",
    "gender": "M"
}
```

`POST: /users/verify_email` - Confirm email

Receives:

```json
{
    "email": "user@example.com",
    "token": "inuRtdsDIaACMkvca4z32ONxKL9QZ74jBiGaFwwgb0fMvcNdiK8+iWUUn1cMIbbPAOrprZyN8IfUU8Yx"
}
```

### Admin

`GET: /admin/list` - Get users list with full information

`GET: /admin/get/{userId}` - Get full info about a specific user

Return data:

```json
{
    "id": 13,
    "firstName": "John",
    "lastName": "Smith",
    "email": "user@example.com",
    "isAdmin": true,
    "dob": {
        "date": "1997-06-25 00:00:00.000000",
        "timezone_type": 3,
        "timezone": "UTC"
    },
    "gender": "M",
    "isEmailVerified": true
}
```

`PUT: /admin/update/{userId}` - Change user data

Receives:

```json
{
    "firstName": "John",
    "lastName": "Smith",
    "email": "user@example.com",
    "isAdmin": true,
    "dob": "1997-06-25",
    "gender": "M"
}
```

`DELETE: /admin/delete/{userId}` - Delete user

### Directories

`POST: /directories/add` - Add a folder

Receives:

```json
{
    "name": "Directory name",
    "parentId": null
}
```

`PUT: /directories/rename/{directoryId}` - Rename a folder

Receives:

```json
{
    "name": "New name"
}
```

`GET: /directories/get` - Get info about the root folder

`GET: /directories/get/{directoryId}` - Get info about a specific folder

Return data:

```json
{
    "properties": {
        "id": 41,
        "name": "Directory name",
        "userId": 13,
        "parentId": null
    },
    "folders": [
        {
            "id": 46,
            "name": "Directory name",
            "userId": 13,
            "parentId": 41
        }
    ],
    "files": [
        {
            "id": 71,
            "name": "night_city.jpg",
            "userId": 13,
            "folderId": 41,
            "createdAt": {
                "date": "2023-11-29 18:02:42.000000",
                "timezone_type": 3,
                "timezone": "UTC"
            },
            "updatedAt": {
                "date": "2023-11-29 18:02:42.000000",
                "timezone_type": 3,
                "timezone": "UTC"
            }
        }
    ]
}
```

`DELETE: /directories/delete/{directoryId}` - Delete folder

### Files

`GET: /files/list` - Get a list of files

`GET: /files/list-shared` - Get a list of files from other users who shared
the file with current user

`GET: /files/get/{fileId}` - Get information about a file

Return data:

```json
{
    "id": 71,
    "name": "night_city.jpg",
    "userId": 13,
    "folderId": 41,
    "createdAt": {
        "date": "2023-11-29 18:02:42.000000",
        "timezone_type": 3,
        "timezone": "UTC"
    },
    "updatedAt": {
        "date": "2023-11-29 18:02:42.000000",
        "timezone_type": 3,
        "timezone": "UTC"
    }
}
```

`GET: /files/download/{fileId}` - Download file

Returns a file.

`POST: /files/add` - Add a file

Receives:

Two POST parameters `folderId` and `file`

`PUT: /files/rename/{fileId}` - Rename a file

Receives:

```json
{
    "name": "New name"
}
```

`DELETE: /files/remove/{fileId}` - Delete file

### Share

`GET: /files/share/{fileId}` - Get users list who have access to file

Receives:

```json
[
    {
        "id": 15,
        "userId": 17,
        "fileId": 71
    }
]
```

`PUT: /files/share/{fileId}/{userId}` - Share file

`DELETE: /files/share/{fileId}/{userId}` - Revoke access to a file for user

## Postman commands

File with Postman commands for testing is inside the `./project_files/`
folder.

## Notes

It's possible to change how internal server errors are handled by modifying  
the config file inside `./src/Config/config.env`.

With `DEV=true` the exception messages are being sent in response body.

With `DEBUG=true` exceptions are thrown and their messages are displayed in
a response body

Also, in config file, you can change maximum file size and default  
total available space for users.
