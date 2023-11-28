<?php

declare(strict_types=1);

namespace Controllers;

use Kernel\ServicesContainer;
use Kernel\Controller;
use Http\HTTPException;
use Http\Request;
use Http\Response;
use Kernel\Models\Repository\MapperRepository;
use Kernel\Models\Repository\DBRepositoryFactory;
use Kernel\Models\Repository\Repository;
use Models\User\User;
use Models\User\UserFactory;
use Models\User\UserMapper;
use Services\AuthService;
use Services\MailerService;
use Services\PDOConnection;

class AdminController extends Controller
{
    /** @var Repository<int, User> **/
    private Repository $userRepository;

    public function __construct(protected ServicesContainer $servicesContainer)
    {
        $this->userRepository = $this->createRepository($servicesContainer);
    }

    /**
     * @return MapperRepository<int, User>
     **/
    protected static function createRepository(ServicesContainer $servicesContainer): MapperRepository
    {
        return DBRepositoryFactory::createRepository(
            UserMapper::class,
            $servicesContainer->get('PDOConnection', PDOConnection::class)->getConnection(),
            'users'
        );
    }

    public function list(Request $request, Response $response): Response
    {
        $users = $this->userRepository->list();

        $userProperties = [];

        foreach ($users as $user) {
            $userProperties[] = $user->getProperties();
        }

        $json = self::toJson($userProperties);

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

        $response->setBody(self::toJson($user->getProperties()));

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = self::argToInt($args['id']);

        $user = $this->getUserById($id);

        $postParameters = self::fromJsonToDataObject($request->getBody());

        $user->update($postParameters);

        $this->userRepository->update($id, $user);

        return $response;
    }

    /**
     * @param array<string, string> $args
     **/
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = self::argToInt($args['id']);

        $user = $this->userRepository->read($id);

        if (is_null($user)) {
            throw new HTTPException(404, 'User not found');
        }

        $fileController = new FileController($this->servicesContainer);
        $fileController->deleteFolder($request, $response, ['id' => strval($user->rootFolderId)]);

        $this->userRepository->delete($id);

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
}
