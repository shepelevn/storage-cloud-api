<?php

declare(strict_types=1);

namespace Models;

use Kernel\Models\Repository\DBMapperRepository;
use Kernel\Models\Repository\DBRepositoryFactory;
use Kernel\ServicesContainer;
use Models\File\FileMapper;
use Models\File\FileModel;
use Models\User\UserMapper;
use Models\Folder\FolderMapper;
use Services\PDOConnection;
use Models\User\User;
use Models\Folder\Folder;
use Models\ShareRecord\ShareRecord;
use Models\ShareRecord\ShareRecordMapper;

class RepositoryFactory
{
    /**
     * @return DBMapperRepository<User>
     **/
    public static function createUserRepository(ServicesContainer $servicesContainer): DBMapperRepository
    {
        return DBRepositoryFactory::createRepository(
            UserMapper::class,
            $servicesContainer->get('PDOConnection', PDOConnection::class)->getConnection(),
            'users'
        );
    }

    /**
     * @return DBMapperRepository<Folder>
     **/
    public static function createFolderRepository(ServicesContainer $servicesContainer): DBMapperRepository
    {
        return DBRepositoryFactory::createRepository(
            FolderMapper::class,
            $servicesContainer->get('PDOConnection', PDOConnection::class)->getConnection(),
            'folders'
        );
    }

    /**
     * @return DBMapperRepository<FileModel>
     **/
    public static function createFileRepository(ServicesContainer $servicesContainer): DBMapperRepository
    {
        return DBRepositoryFactory::createRepository(
            FileMapper::class,
            $servicesContainer->get('PDOConnection', PDOConnection::class)->getConnection(),
            'files'
        );
    }

    /**
     * @return DBMapperRepository<ShareRecord>
     **/
    public static function createShareRecordRepository(ServicesContainer $servicesContainer): DBMapperRepository
    {
        return DBRepositoryFactory::createRepository(
            ShareRecordMapper::class,
            $servicesContainer->get('PDOConnection', PDOConnection::class)->getConnection(),
            'share_records'
        );
    }
}
