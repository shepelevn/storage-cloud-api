<?php

declare(strict_types=1);

namespace Controllers;

use DateTimeImmutable;
use Exception;
use Kernel\ServicesContainer;
use Kernel\Controller;
use Http\HTTPException;
use Http\Request;
use Http\Response;
use Kernel\Models\Repository\DBMapperRepository;
use LogicException;
use Models\File\FileFactory;
use Models\Folder\Folder;
use Models\File\FileModel;
use Models\Folder\FolderFactory;
use Models\RepositoryFactory;
use Models\ShareRecord\ShareRecord;
use Models\ShareRecord\ShareRecordFactory;
use Services\AuthService;
use Models\User\User;
use RuntimeException;
use Services\FilesService;

class FileController extends Controller
{
    /** @var DBMapperRepository<User> **/
    private DBMapperRepository $userRepository;
    /** @var DBMapperRepository<Folder> **/
    private DBMapperRepository $folderRepository;
    /** @var DBMapperRepository<FileModel> **/
    private DBMapperRepository $fileRepository;
    /** @var DBMapperRepository<ShareRecord> **/
    private DBMapperRepository $shareRecordRepository;

    public function __construct(protected ServicesContainer $servicesContainer)
    {
        $this->userRepository = RepositoryFactory::createUserRepository($servicesContainer);
        $this->folderRepository = RepositoryFactory::createFolderRepository($servicesContainer);
        $this->fileRepository = RepositoryFactory::createFileRepository($servicesContainer);
        $this->shareRecordRepository = RepositoryFactory::createShareRecordRepository($servicesContainer);
    }

    public function listFiles(Request $request, Response $response): Response
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $userId = $authService->getId();

        $files = $this->fileRepository->getByProperty('user_id', $userId);
        $filesArray = array_map(fn ($file) => $file->getProperties(), $files);

        $response->setBody(self::toJson($filesArray));

        return $response;
    }

    public function listSharedFiles(Request $request, Response $response): Response
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $userId = $authService->getId();

        $shareRecords = $this->shareRecordRepository->getByProperty('user_id', $userId);

        $files = [];

        foreach ($shareRecords as $record) {
            $file = $this->fileRepository->read($record->fileId);

            if (!is_null($file)) {
                $files[] = $file;
            }
        }

        $filesArray = array_map(fn ($file) => $file->getProperties(), $files);

        $response->setBody(self::toJson($filesArray));

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function getFile(Request $request, Response $response, array $args): Response
    {
        $id = self::argToInt($args['id']);
        $file = $this->getFileById($id);

        $this->checkSafeFileAccess($file);

        $response->setBody(self::toJson($file->getProperties()));

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function download(Request $request, Response $response, array $args): Response
    {
        $filesService = $this->servicesContainer->get('FilesService', FilesService::class);

        $id = self::argToInt($args['id']);
        $file = $this->getFileById($id);

        $this->checkSafeFileAccess($file);

        $fileData = $filesService->get((string) $file->id);

        $response = $response
            ->withHeader('Content-Type', $fileData['type'])
            ->withHeader('Content-Disposition', 'attachment; filename="' . $file->name . '"');

        $response->setBody($fileData['contents']);

        return $response;
    }

    public function addFile(Request $request, Response $response): Response
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $userId = $authService->getId();

        $user = $this->userRepository->read($userId);
        if ($user === null) {
            throw new RuntimeException('Logged in user not found');
        }

        $filesService = $this->servicesContainer->get('FilesService', FilesService::class);

        $uploadedFilesArray = $request->getFilesArray();
        $folderId = (int) $request->getPostArray()['folderId'];

        $folder = $this->folderRepository->read($folderId);

        if (is_null($folder)) {
            throw new HTTPException(400, 'This folder does not exist');
        }

        $this->checkFolderAccess($folder);

        if (!isset($uploadedFilesArray['file'])) {
            throw new HTTPException(400, 'No file posted');
        }

        $uploadedFile = $uploadedFilesArray['file'];

        $file = FileFactory::createFileTemplate();

        $file->name = $uploadedFile->getName();
        $file->userId = $userId;
        $file->folderId = $folderId;
        $file->createdAt = new DateTimeImmutable();
        $file->updatedAt = new DateTimeImmutable();

        $sameNameFiles = $this->fileRepository->getByProperty('name', $file->name);

        foreach ($sameNameFiles as $sameNameFile) {
            $file->checkAreUnique($sameNameFile);
        }

        $user->storageUsedKb = $user->storageUsedKb + $uploadedFile->getSizeKb();

        $fileId = $this->fileRepository->create($file);

        try {
            $filesService->save($uploadedFile, (string) $fileId);
            $this->userRepository->update($user->id, $user);
        } catch (Exception $exception) {
            $this->fileRepository->delete($fileId);
            throw $exception;
        }

        return $response->withStatus(201);
    }

    /**
     * @param array<string, string> $args
     **/
    public function renameFile(Request $request, Response $response, array $args): Response
    {
        $id = self::argToInt($args['id']);
        $file = $this->getFileById($id);

        $this->checkFileAccess($file);

        $postParameters = self::fromJsonToDataObject($request->getBody());

        $file->setName($postParameters->checkAndGet('name'));
        $file->updatedAt = new DateTimeImmutable();

        $this->fileRepository->update($id, $file);

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function deleteFileAction(Request $request, Response $response, array $args): Response
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $userId = $authService->getId();
        $user = $this->userRepository->read($userId);

        if (is_null($user)) {
            throw new RuntimeException('Logged in user not found');
        }

        $id = self::argToInt($args['id']);

        $file = $this->getFileById($id);

        if (!$user->isAdmin) {
            $this->checkFileAccess($file);
        }

        $this->deleteFile($file->id);

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function getFolder(Request $request, Response $response, array $args): Response
    {
        $id = self::argToInt($args['id']);
        $folder = $this->getFolderById($id);

        $this->checkFolderAccess($folder);

        $response->setBody($this->getFolderInfoJson($folder));

        return $response;
    }

    public function getRootFolder(Request $request, Response $response): Response
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $userId = $authService->getId();

        $user = $this->userRepository->read($userId);

        if (is_null($user)) {
            throw new RuntimeException('User is null');
        }

        $rootFolderId = $user->rootFolderId;

        $rootFolder = $this->getFolderById($rootFolderId);

        $response->setBody($this->getFolderInfoJson($rootFolder));

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function renameFolder(Request $request, Response $response, array $args): Response
    {
        $id = self::argToInt($args['id']);
        $folder = $this->getFolderById($id);

        $this->checkFolderAccess($folder);

        $postParameters = self::fromJsonToDataObject($request->getBody());

        $folder->setName($postParameters->checkAndGet('name'));

        $this->folderRepository->update($id, $folder);

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function deleteFolder(Request $request, Response $response, array $args): Response
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $userId = $authService->getId();
        $user = $this->userRepository->read($userId);

        if (is_null($user)) {
            throw new RuntimeException('Logged in user not found');
        }

        $id = self::argToInt($args['id']);
        $folder = $this->getFolderById($id);

        if (!$user->isAdmin) {
            $this->checkFolderAccess($folder);
        }

        $this->deleteFolderRecursively($folder);

        return $response;
    }

    public function addFolder(Request $request, Response $response): Response
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $userId = $authService->getId();

        $folder = FolderFactory::createFolderTemplate();

        $postParameters = self::fromJsonToDataObject($request->getBody());

        $folder->update($postParameters);

        if (is_null($folder->parentId)) {
            $user = $this->userRepository->read($userId);

            if (is_null($user)) {
                throw new RuntimeException('Logged in user not found');
            }

            $folder->parentId = $user->rootFolderId;
        }

        $parentFolder = $this->folderRepository->read($folder->parentId);

        if (is_null($parentFolder)) {
            throw new LogicException('Could not get the parent folder');
        }

        if ($parentFolder->userId !== $userId) {
            throw new HTTPException(403, 'Denied access to parent folder');
        }

        $folder->userId = $userId;

        $sameNameFolders = $this->folderRepository->getByProperty('name', $folder->name);

        foreach ($sameNameFolders as $sameNameFolder) {
            $folder->checkAreUnique($sameNameFolder);
        }

        $this->folderRepository->create($folder);

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function getShareRecords(Request $request, Response $response, array $args): Response
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $userId = $authService->getId();

        $fileId = self::argToInt($args['fileId']);

        $file = $this->fileRepository->read($fileId);

        if (is_null($file)) {
            throw new HTTPException(404, 'File not found');
        }

        if ($file->userId !== $userId) {
            throw new HTTPException(403, 'This is not your file');
        }

        $shareRecords = $this->shareRecordRepository->getByProperty('file_id', $fileId);

        $recordsArray = array_map(fn ($record) => $record->getProperties(), $shareRecords);

        $response->setBody(self::toJson($recordsArray));

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function shareFile(Request $request, Response $response, array $args): Response
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $authId = $authService->getId();

        $fileId = self::argToInt($args['fileId']);
        $userId = self::argToInt($args['userId']);

        $user = $this->userRepository->read($userId);

        if (is_null($user)) {
            throw new HTTPException(404, 'User not found');
        }

        $file = $this->fileRepository->read($fileId);

        if (is_null($file)) {
            throw new HTTPException(404, 'File not found');
        }

        if ($file->userId !== $authId) {
            throw new HTTPException(403, 'You can not share this file');
        }

        $shareRecord = ShareRecordFactory::createShareRecordTemplate();
        $shareRecord->fileId = $fileId;
        $shareRecord->userId = $userId;

        $fileShareRecords = $this->shareRecordRepository->getByProperty('file_id', $fileId);

        foreach ($fileShareRecords as $record) {
            $shareRecord->checkAreUnique($record);
        }

        $this->shareRecordRepository->create($shareRecord);

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function unshareFile(Request $request, Response $response, array $args): Response
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $authId = $authService->getId();

        $fileId = self::argToInt($args['fileId']);
        $userId = self::argToInt($args['userId']);

        $file = $this->fileRepository->read($fileId);

        if (is_null($file)) {
            throw new HTTPException(404, 'File not found');
        }

        if ($file->userId !== $authId) {
            throw new HTTPException(403, 'You can not unshare this file');
        }

        $fileShareRecords = $this->shareRecordRepository->getByProperty('file_id', $fileId);

        foreach ($fileShareRecords as $record) {
            if ($userId === $record->userId) {
                $this->shareRecordRepository->delete($record->id);
                return $response;
            }
        }

        throw new HTTPException(404, 'Share record not found');
    }

    private function deleteFolderRecursively(Folder $folder): void
    {
        $childFolders = $this->folderRepository->getByProperty('parent_id', $folder->id);

        foreach ($childFolders as $childFolder) {
            $this->deleteFolderRecursively($childFolder);
        }

        $childFiles = $this->fileRepository->getByProperty('folder_id', $folder->id);

        foreach ($childFiles as $childFile) {
            $this->deleteFile($childFile->id);
        }

        $this->folderRepository->delete($folder->id);
    }

    private function deleteFile(int $id): void
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $userId = $authService->getId();

        $user = $this->userRepository->read($userId);
        if ($user === null) {
            throw new RuntimeException('Logged in user not found');
        }

        $filesService = $this->servicesContainer->get('FilesService', FilesService::class);

        $shareRecords = $this->shareRecordRepository->getByProperty('file_id', $id);

        foreach ($shareRecords as $record) {
            $this->shareRecordRepository->delete($record->id);
        }

        $user->storageUsedKb = $user->storageUsedKb - $filesService->getSizeKb((string) $id);

        $this->fileRepository->delete($id);
        $filesService->delete((string) $id);

        $this->userRepository->update($user->id, $user);
    }

    private function getFolderById(int $id): Folder
    {
        $folder = $this->folderRepository->read($id);

        if (is_null($folder)) {
            throw new HTTPException(404, 'Folder not found');
        }

        return $folder;
    }

    private function getFileById(int $id): FileModel
    {
        $file = $this->fileRepository->read($id);

        if (is_null($file)) {
            throw new HTTPException(404, 'File not found');
        }

        return $file;
    }

    private function checkFolderAccess(Folder $folder): void
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $userId = $authService->getId();

        $user = $this->userRepository->read($userId);

        if (is_null($user)) {
            throw new HTTPException(403, 'User you logged in as is not found');
        }

        $folder->checkAccess($userId);
    }

    private function checkFileAccess(FileModel $file): void
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $userId = $authService->getId();

        $user = $this->userRepository->read($userId);

        if (is_null($user)) {
            throw new HTTPException(403, 'User you logged in as is not found');
        }

        $file->checkAccess($userId);
    }

    private function checkSafeFileAccess(FileModel $file): void
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);
        $userId = $authService->getId();

        $shareRecords = $this->shareRecordRepository->getByProperty('file_id', $file->id);

        foreach ($shareRecords as $record) {
            if ($record->userId === $userId) {
                return;
            }
        }

        $file->checkAccess($userId);
    }

    private function getFolderInfoJson(Folder $folder): string
    {
        $folderInfo = [];
        $folderInfo['properties'] = $folder->getProperties();
        $folderInfo['folders'] = $this->getChildFoldersData($folder);
        $folderInfo['files'] = $this->getChildFilesData($folder);

        return self::toJson($folderInfo);
    }

    /**
     * @return list<array<string, DataValue>>
     **/
    private function getChildFoldersData(Folder $folder): array
    {
        $childFolders = $this->folderRepository->getByProperty('parent_id', $folder->id);

        return array_map(fn ($childFolder) => $childFolder->getProperties(), $childFolders);
    }

    /**
     * @return list<array<string, DataValue>>
     **/
    private function getChildFilesData(Folder $folder): array
    {
        $childFiles = $this->fileRepository->getByProperty('folder_id', $folder->id);

        return array_map(fn ($childFile) => $childFile->getProperties(), $childFiles);
    }
}
