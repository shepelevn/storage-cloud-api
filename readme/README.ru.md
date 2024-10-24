# REST API для веб-приложения облачного хранилища

## README.md

* en [English](../README.md)
* ru [Русский](README.ru.md)

## Содержание

* [Технологии используемые в проекте](#технологии-используемые-в-проекте)
* [Развертывание проекта](#развертывание-проекта)
* [Импорт базы данных](#импорт-базы-данных)
* [Конфигурация](#конфигурация)
* [Настройка Apache](#настройка-apache)
* [Эндпойнты сервера](#эндпойнты-сервера)
  * [Примечания по данным](#примечания-по-данным)
  * [Users](#users)
  * [Admin](#admin)
  * [Directories](#directories)
  * [Files](#files)
  * [Share](#share)
* [Команды postman](#команды-postman)
* [Примечания](#примечания)

## Технологии используемые в проекте

* `PHP`
* `PHPStan`

## Развертывание проекта

Действия при развертывании проекта:

* Импортировать базу данных
* Создать файлы конфигурации
* Настроить Apache

## Импорт базы данных

Импорт базы данных производится с помощью команды

```bash
mysql -u username -p [Имя базы данных] < "./project_files/database_dump.sql"
```

## Конфигурация

Необходимо создать файлы конфигурации с данными для SMTP и MySQL в
папке `./src/Config/Secret/`.
Пример конфигурации можно найти в папке `./src/Config/SecretExample/`.

## Запуск сервера для разработки

Для запуска проекта можно настроить Apache или можно запустить сервер для разработки
с помощью скрипта `./devServer.sh`.

## Настройка Apache

Конфигурационный файл вашего виртуального сервера должен выглядеть
примерно так (используется каталог по умолчанию — `/var/www/html`):

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

Убедитесь, что активирован `apache_mod_rewrite`.

## Эндпойнты сервера

### Примечания по данным

`dob`, `gender` могут быть `null`. `lastName` может быть пустой строкой.
`gender` принимает значения "M" и "F"

`parentId` в значении `null` принимается как корневая директория.

### Users

`GET: /users/list` - Получить список пользователей с безопасной информацией

`GET: /users/get/{userId}` - Получить безопасную информацию конкретного
пользователя

`GET: /users/search/{email}` - Поиск пользователей по email

Возвращают:

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

`PUT: /users/update` - Изменить информацию пользователя текущей сессии

Принимает:

```json
{
    "firstName": "John",
    "lastName": "Smith",
    "dob": "1997-06-25",
    "gender": "M"
}
```

`POST: /users/login` - Войти в аккаунт.

Принимает:

```json
{
    "email": "user@example.com",
    "password": "password"
}
```

`GET: /users/reset_password?email=username@example.com` - Отправить токен
сброса пароля на почту

Принимает: Query параметр `email`

`POST: /users/reset_password` - Сбросить пароль

Принимает:

```json
{
    "email": "user@example.com",
    "password": "password",
    "token": "P+axG5k9gStneqbLmH9K4e3u8DIv0Bdh6iPUDu4zFbxuyd7GjuUBWXPc/x8eTgb+Mh75WuTF41jlu5Qh"
}
```

`POST: /users/register` - Зарегистрироваться

Принимает:

```json
{
    "firstName": "John",
    "lastName": "Smith",
    "email":"user1@example.com",
    "password": "password",
    "dob": "1997-06-25",
    "gender": "M"
}
```

`POST: /users/verify_email` - Подтвердить email

Принимает:

```json
{
    "email": "user@example.com",
    "token": "inuRtdsDIaACMkvca4z32ONxKL9QZ74jBiGaFwwgb0fMvcNdiK8+iWUUn1cMIbbPAOrprZyN8IfUU8Yx"
}
```

### Admin

`GET: /admin/list` - Получить список пользователей с полной информацией

`GET: /admin/get/{userId}` - Получить полную информацию конкретного
пользователя

Возвращают:

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

`PUT: /admin/update/{userId}` - Изменить данные пользователя

Принимает:

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

`DELETE: /admin/delete/{userId}` - Удалить пользователя

### Directories

`POST: /directories/add` - Добавить папку.

Принимает:

```json
{
    "name": "Directory name",
    "parentId": null
}
```

`PUT: /directories/rename/{directoryId}` - Переименовать папку

Принимает:

```json
{
    "name": "New name"
}
```

`GET: /directories/get` - Получить информацию о корневой папке

`GET: /directories/get/{directoryId}` - Получить информацию о конкретной
папке

Возвращают:

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

`DELETE: /directories/delete/{directoryId}` - Удалить папку

### Files

`GET: /files/list` - Получить список файлов

`GET: /files/list-shared` - Получить список файлов других пользователей
к которым предоставлен доступ

`GET: /files/get/{fileId}` - Получить информацию о файле

Возвращают:

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

`GET: /files/download/{fileId}` - Скачать файл

Возвращает файл.

`POST: /files/add` - Добавить файл

Принимает:

Два POST параметра `folderId` и `file`

`PUT: /files/rename/{fileId}` - Переименовать файл

Принимает:

```json
{
    "name": "New name"
}
```

`DELETE: /files/remove/{fileId}` - Удалить файл

### Share

`GET: /files/share/{fileId}` - Получить список пользователей которым
предоставлен доступ к файлу

Возвращает:

```json
[
    {
        "id": 15,
        "userId": 17,
        "fileId": 71
    }
]
```

`PUT: /files/share/{fileId}/{userId}` - Поделиться файлом

`DELETE: /files/share/{fileId}/{userId}` - Отменить доступ к файлу

## Команды postman

Файл с командами Postman для тестирования находится в папке
`./project_files/`.

## Примечания

В файле конфига `./src/Config/config.env` можно поменять настройки
обработки внутренних ошибок сервера.

При `DEV=true` сообщения исключений выводятся в теле ответа.

При `DEBUG=true` исключения выкидываются и выводятся в теле сообщения.

Также в конфиге можно поменять максимальный размер файла и доступный
размер хранилища пользователя.
