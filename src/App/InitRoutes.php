<?php

declare(strict_types=1);

namespace App;

use Controllers\AdminController;
use Controllers\ErrorController;
use Controllers\FileController;
use Controllers\UserController;
use Kernel\Kernel;
use Middleware\Auth;

class InitRoutes
{
    public function __invoke(Kernel $kernel): void
    {
        /* Users */
        $usersGroup = $kernel->routesResolver->addGroup('/users');

        $usersGroup->addGet('/list', [UserController::class, 'list']);
        $usersGroup->addGet('/get/{id}', [UserController::class, 'get']);
        $usersGroup->addPut('/update', [UserController::class, 'update']);
        $usersGroup->addGet('/search/{email}', [UserController::class, 'searchByEmail']);

        $usersGroup->addMiddleware(new Auth());

        /* Authentication */
        $authorizedAuthGroup = $kernel->routesResolver->addGroup('');
        $unauthorizedAuthGroup = $kernel->routesResolver->addGroup('');

        /* Without authorization */
        $unauthorizedAuthGroup->addPost('/login', [UserController::class, 'login']);
        $unauthorizedAuthGroup->addGet('/reset_password', [UserController::class, 'sendResetPasswordToken']);
        $unauthorizedAuthGroup->addPost('/reset_password', [UserController::class, 'resetPassword']);
        $unauthorizedAuthGroup->addPost('/register', [UserController::class, 'register']);
        $unauthorizedAuthGroup->addPost('/verify_email', [UserController::class, 'verifyEmail']);

        /* With authorization */
        $authorizedAuthGroup->addGet('/logout', [UserController::class, 'logout']);

        $authorizedAuthGroup->addMiddleware(new Auth());

        /* Admin */
        $adminGroup = $kernel->routesResolver->addGroup('/admin/users');
        $adminGroup->addGet('/list', [AdminController::class, 'list']);
        $adminGroup->addGet('/get/{id}', [AdminController::class, 'get']);
        $adminGroup->addPut('/update/{id}', [AdminController::class, 'update']);
        $adminGroup->addDelete('/delete/{id}', [AdminController::class, 'delete']);

        $adminGroup->addMiddleware(new Auth('admin'));

        /* Directories */
        $directoriesGroup = $kernel->routesResolver->addGroup('/directories');
        $directoriesGroup->addPost('/add', [FileController::class, 'addFolder']);
        $directoriesGroup->addPut('/rename/{id}', [FileController::class, 'renameFolder']);
        $directoriesGroup->addGet('/get', [FileController::class, 'getRootFolder']);
        $directoriesGroup->addGet('/get/{id}', [FileController::class, 'getFolder']);
        $directoriesGroup->addDelete('/delete/{id}', [FileController::class, 'deleteFolder']);

        $directoriesGroup->addMiddleware(new Auth());

        /* Files */
        $filesGroup = $kernel->routesResolver->addGroup('/files');
        $filesGroup->addGet('/list', [FileController::class, 'listFiles']);
        $filesGroup->addGet('/list-shared', [FileController::class, 'listSharedFiles']);
        $filesGroup->addGet('/get/{id}', [FileController::class, 'getFile']);
        $filesGroup->addGet('/download/{id}', [FileController::class, 'download']);
        $filesGroup->addPost('/add', [FileController::class, 'addFile']);
        $filesGroup->addPut('/rename/{id}', [FileController::class, 'renameFile']);
        $filesGroup->addDelete('/remove/{id}', [FileController::class, 'deleteFileAction']);

        $filesGroup->addMiddleware(new Auth());

        /* Share */
        $shareGroup = $kernel->routesResolver->addGroup('/files/share');
        $shareGroup->addGet('/{fileId}', [FileController::class, 'getShareRecords']);
        $shareGroup->addPut('/{fileId}/{userId}', [FileController::class, 'shareFile']);
        $shareGroup->addDelete('/{fileId}/{userId}', [FileController::class, 'unshareFile']);

        /* 404 */
        $kernel->routesResolver->addAll('*', [ErrorController::class, 'notFound']);
    }
}
