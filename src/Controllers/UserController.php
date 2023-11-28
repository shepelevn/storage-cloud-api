<?php

declare(strict_types=1);

namespace Controllers;

use Kernel\ServicesContainer;
use Kernel\Controller;
use Http\HTTPException;
use Http\Request;
use Http\Response;
use Kernel\Models\Repository\DBMapperRepository;
use Models\RepositoryFactory;
use Models\User\User;
use Models\Folder\Folder;
use Models\Folder\FolderFactory;
use Models\User\UserFactory;
use Services\AuthService;
use Services\ConfigService;
use Services\MailerService;

class UserController extends Controller
{
    /** @var DBMapperRepository<User> **/
    private DBMapperRepository $userRepository;
    /** @var DBMapperRepository<Folder> **/
    private DBMapperRepository $folderRepository;

    public function __construct(protected ServicesContainer $servicesContainer)
    {
        $this->userRepository = RepositoryFactory::createUserRepository($servicesContainer);
        $this->folderRepository = RepositoryFactory::createFolderRepository($servicesContainer);
    }

    public function list(Request $request, Response $response): Response
    {
        $users = $this->userRepository->list();

        $userSafeProperties = [];

        foreach ($users as $user) {
            $userSafeProperties[] = $user->getSafeProperties();
        }

        $json = self::toJson($userSafeProperties);

        $response->setBody($json);

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function get(Request $request, Response $response, array $args): Response
    {
        $id = self::argToInt($args['id']);
        $user = $this->getUserById($id);

        $response->setBody(self::toJson($user->getSafeProperties()));

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function searchByEmail(Request $request, Response $response, array $args): Response
    {
        $email = $args['email'];

        $users = $this->userRepository->getLikeProperty('email', $email);

        $userSafeProperties = [];

        foreach ($users as $user) {
            $userSafeProperties[] = $user->getSafeProperties();
        }

        $json = self::toJson($userSafeProperties);

        $response->setBody($json);

        return $response;
    }

    public function update(Request $request, Response $response): Response
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);

        $id = $authService->getId();

        $user = $this->getUserById($id);

        $postParameters = self::fromJsonToDataObject($request->getBody());

        $user->safeUpdate($postParameters);

        $this->userRepository->update($id, $user);

        return $response;
    }

    public function getUserById(int $id): User
    {
        $user = $this->userRepository->read($id);

        if (is_null($user)) {
            throw new HTTPException(404, 'User not found');
        }

        return $user;
    }

    public function sendResetPasswordToken(Request $request, Response $response): Response
    {
        $email = $request->getQueryParams()['email'];

        $users = $this->userRepository->getByProperty('email', $email);

        if (!isset($users[0])) {
            throw new HTTPException(404, 'User with this email is not found');
        }

        $user = $users[0];

        $mailer = $this->servicesContainer->get('MailerService', MailerService::class);

        $user->addPasswordResetToken($mailer);

        $this->userRepository->update($user->id, $user);

        return $response;
    }

    public function resetPassword(Request $request, Response $response): Response
    {
        $loginData = self::fromJsonToDataObject($request->getBody());

        $email = $loginData->checkAndGet('email');
        $password = $loginData->checkAndGet('password');
        $token = $loginData->checkAndGet('token');

        $users = $this->userRepository->getByProperty('email', $email);

        if (!isset($users[0])) {
            throw new HTTPException(404, "User with email: $email not found");
        }

        $user = $users[0];
        $user->resetPassword($token, $password);

        $this->userRepository->update($user->id, $user);

        return $response;
    }

    public function register(Request $request, Response $response): Response
    {
        $user = UserFactory::createEmptyUser();

        $postParameters = self::fromJsonToDataObject($request->getBody());

        $user->updateOnCreate($postParameters);

        $mainConfig = $this->servicesContainer->get('ConfigService', ConfigService::class)->mainConfig;
        $defaultStorageSizeKb = (int) $mainConfig['DEFAULT_STORAGE_SIZE_MB'] * 1024;
        $minStorageSizeKb = (int) $mainConfig['MIN_STORAGE_SIZE_MB'] * 1024;
        $maxStorageSizeKb = (int) $mainConfig['MAX_STORAGE_SIZE_MB'] * 1024;

        $user->setStorageSize($defaultStorageSizeKb, $minStorageSizeKb, $maxStorageSizeKb);

        $sameEmailUsers = $this->userRepository->getByProperty('email', $user->email);

        if (count($sameEmailUsers) > 0) {
            throw new HTTPException(400, 'The user with this email is already registered');
        }

        $mailer = $this->servicesContainer->get('MailerService', MailerService::class);

        $user->addEmailVerificationToken($mailer);

        $user->id = $this->userRepository->create($user);

        $this->createRootFolder($user);

        return $response;
    }

    public function verifyEmail(Request $request, Response $response): Response
    {
        $loginData = self::fromJsonToDataObject($request->getBody());

        $email = $loginData->checkAndGet('email');
        $token = $loginData->checkAndGet('token');

        $users = $this->userRepository->getByProperty('email', $email);

        if (!isset($users[0])) {
            throw new HTTPException(404, "User with email: $email not found");
        }

        $user = $users[0];

        if ($user->isEmailVerificationTokenExpired()) {
            $this->folderRepository->delete($user->rootFolderId);
            $this->userRepository->delete($user->id);

            throw new HTTPException(401, 'Email verification token is expired. User is deleted.');
        }

        $user->verifyEmail($token);

        $this->userRepository->update($user->id, $user);

        return $response;
    }

    public function login(Request $request, Response $response): Response
    {
        $loginData = self::fromJsonToDataObject($request->getBody());

        $email = $loginData->checkAndGet('email');
        $password = $loginData->checkAndGet('password');

        $users = $this->userRepository->getByProperty('email', $email);

        if (!isset($users[0]) || !$users[0]->checkPassword($password)) {
            throw new HTTPException(401, 'Wrong email or password');
        }

        $user = $users[0];

        if (!$user->isEmailVerified) {
            throw new HTTPException(401, 'Email not verified');
        }

        $authService = $this->servicesContainer->get('AuthService', AuthService::class);

        $authService->createUserSession($user->id, $user->isAdmin);

        return $response;
    }

    public function logout(Request $request, Response $response): Response
    {
        $authService = $this->servicesContainer->get('AuthService', AuthService::class);

        $authService->destroyUserSession();

        return $response;
    }

    public function createRootFolder(User $user): void
    {
        $rootFolder = FolderFactory::createFolderTemplate();

        $rootFolder->name = (string) $user->id;
        $rootFolder->userId = $user->id;
        $rootFolder->parentId = null;

        $rootFolderId = $this->folderRepository->create($rootFolder);

        $user->rootFolderId = $rootFolderId;

        $this->userRepository->update($user->id, $user);
    }
}
