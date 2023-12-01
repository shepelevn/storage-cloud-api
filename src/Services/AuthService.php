<?php

declare(strict_types=1);

namespace Services;

use LogicException;

class AuthService
{
    public readonly bool $isLoggedIn;
    private int | null $id;
    public readonly bool $isAdmin;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION['id'])) {
            $this->isLoggedIn = true;
            $this->id = $_SESSION['id'];
            $this->isAdmin = $_SESSION['isAdmin'];
        } else {
            $this->isLoggedIn = false;
            $this->id = null;
            $this->isAdmin = false;
        }
    }

    public function createUserSession(int $id, bool $isAdmin): void
    {
        $_SESSION['id'] = $id;
        $_SESSION['isAdmin'] = $isAdmin;
    }

    public function destroyUserSession(): void
    {
        session_destroy();
    }

    public function getId(): int
    {
        if (is_null($this->id)) {
            throw new LogicException('Tried to access id while not being logged in');
        }

        return $this->id;
    }
}
