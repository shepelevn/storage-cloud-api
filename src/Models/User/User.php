<?php

declare(strict_types=1);

namespace Models\User;

use DateInterval;
use DateTimeImmutable;
use Exception;
use Http\HTTPException;
use Kernel\Models\BodyData;
use LogicException;
use Services\MailerService;

/**
 * @property string $firstName
 * @property string $lastName
 * @property string $email
 * @property string $password
 * @property bool $isAdmin
 * @property DateTimeImmutable | null $dob
 * @property string | null $gender
 * @property int $storageUsedKb
 **/
class User
{
    public const PASSWORD_TOKEN_EXPIRATION_PERIOD = 'P1D';
    public const EMAIL_TOKEN_EXPIRATION_PERIOD = 'P1D';


    /**
     * @param non-empty-string $firstName
     **/
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        private string $email,
        private string $password,
        private bool $isAdmin,
        private DateTimeImmutable | null $dob = null,
        private string | null $gender = null,
        public string | null $passwordResetToken,
        public DateTimeImmutable | null $passwordResetTokenExpiration,
        public string | null $emailVerificationToken,
        public DateTimeImmutable | null $emailVerificationTokenExpiration,
        public bool $isEmailVerified,
        public int $rootFolderId,
        private int $storageUsedKb,
        public int $storageSizeKb,
    ) {
    }

    public static function hashPassword(string $rawPassword): string
    {
        return password_hash($rawPassword, PASSWORD_DEFAULT);
    }

    public static function generateToken(): string
    {
        return base64_encode(random_bytes(60));
    }

    /**
     * @return array<string, DataValue>
     **/
    public function getSafeProperties(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'dob' => $this->dob,
            'gender' => $this->gender,
        ];
    }

    /**
     * @return array<string, DataValue>
     **/
    public function getProperties(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'isAdmin' => $this->isAdmin,
            'dob' => $this->dob,
            'gender' => $this->gender,
            'isEmailVerified' => $this->isEmailVerified,
        ];
    }

    public function safeUpdate(BodyData $parameters): void
    {
        $this->setFirstName($parameters->checkAndGet('firstName'));
        $this->setLastName($parameters->checkAndGet('lastName'));
        $this->setDobFromString($parameters->checkAndGet('dob'));
        $this->setGender($parameters->checkAndGet('gender'));
    }

    public function updateOnCreate(BodyData $parameters): void
    {
        $this->setFirstName($parameters->checkAndGet('firstName'));
        $this->setLastName($parameters->checkAndGet('lastName'));
        $this->setEmail($parameters->checkAndGet('email'));
        $this->setPassword($parameters->checkAndGet('password'));
        $this->setDobFromString($parameters->checkAndGet('dob'));
        $this->setGender($parameters->checkAndGet('gender'));
    }

    public function update(BodyData $parameters): void
    {
        $this->setFirstName($parameters->checkAndGet('firstName'));
        $this->setLastName($parameters->checkAndGet('lastName'));
        $this->setEmail($parameters->checkAndGet('email'));
        $this->setIsAdmin($parameters->checkAndGet('isAdmin'));
        $this->setDobFromString($parameters->checkAndGet('dob'));
        $this->setGender($parameters->checkAndGet('gender'));
    }

    /**
     * @param JSONValue $password
     **/
    public function checkPassword(mixed $password): bool
    {
        if (!is_string($password)) {
            throw new HTTPException(400, 'Password is not a string');
        }

        return password_verify($password, $this->password);
    }

    public function addPasswordResetToken(MailerService $mailer): void
    {
        $token = self::generateToken();
        $this->passwordResetToken = $token;
        $expirationPeriod = new DateInterval(self::PASSWORD_TOKEN_EXPIRATION_PERIOD);
        $now = new DateTimeImmutable();
        $this->passwordResetTokenExpiration = $now->add($expirationPeriod);

        $mailer->sendEmail(
            'Облачное хранилище',
            'Сброс пароля',
            "Email: $this->email Токен: $token",
            $this->email
        );
    }

    private function resetPasswordResetToken(): void
    {
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiration = null;
    }

    public function setStorageSize(int $newStorageSizeKb, int $minStorageSizeKb, int $maxStorageSizeKb): void
    {
        if ($newStorageSizeKb < $minStorageSizeKb) {
            throw new LogicException("User storage size can not be less than $minStorageSizeKb");
        }

        if ($newStorageSizeKb > $maxStorageSizeKb) {
            throw new LogicException("User storage size can not be more than $minStorageSizeKb");
        }

        $this->storageSizeKb = $newStorageSizeKb;
    }

    /**
     * @param JSONValue $token
     * @param JSONValue $password
     **/
    public function resetPassword(mixed $token, mixed $password): void
    {

        if ($this->passwordResetTokenExpiration < new DateTimeImmutable()) {
            $this->resetPasswordResetToken();
            throw new HTTPException(401, 'Token expired');
        }

        if (!is_string($token)) {
            throw new HTTPException(400, 'Token is not string');
        }

        if ($this->passwordResetToken !== $token) {
            throw new HTTPException(401, 'Wrong password reset token');
        }

        $this->setPassword($password);
        $this->resetPasswordResetToken();
    }

    public function addEmailVerificationToken(MailerService $mailer): string
    {
        $token = self::generateToken();
        $this->emailVerificationToken = $token;
        $expirationPeriod = new DateInterval(self::EMAIL_TOKEN_EXPIRATION_PERIOD);
        $now = new DateTimeImmutable();
        $this->emailVerificationTokenExpiration = $now->add($expirationPeriod);

        $mailer->sendEmail(
            'Облачное хранилище',
            'Подтверждение электронной почты',
            "Email: $this->email Токен: $token",
            $this->email
        );

        return $token;
    }

    private function resetEmailVerificationToken(): void
    {
        $this->emailVerificationToken = null;
        $this->emailVerificationTokenExpiration = null;
    }

    public function isEmailVerificationTokenExpired(): bool
    {
        if (is_null($this->emailVerificationTokenExpiration)) {
            return false;
        }

        if ($this->emailVerificationTokenExpiration < new DateTimeImmutable()) {
            return true;
        }

        return false;
    }

    /**
     * @param JSONValue $token
     **/
    public function verifyEmail(mixed $token): void
    {
        if ($this->isEmailVerified) {
            throw new HTTPException(400, 'This user email is already verified');
        }

        if (!is_string($token)) {
            throw new HTTPException(400, 'Token is not string');
        }

        if ($this->emailVerificationToken !== $token) {
            throw new HTTPException(401, 'Wrong email verification token');
        }

        $this->isEmailVerified = true;
        $this->resetEmailVerificationToken();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getIsAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function getDob(): DateTimeImmutable | null
    {
        return $this->dob;
    }

    public function getGender(): string | null
    {
        return $this->gender;
    }

    public function getStorageUsed(): int
    {
        return $this->storageUsedKb;
    }

    /**
     * @param JSONValue $firstName
     **/
    public function setFirstName(mixed $firstName): void
    {
        if (!is_string($firstName)) {
            throw new HTTPException(400, 'firstName is not string');
        }

        if ($firstName === '') {
            throw new HTTPException(400, 'FirstName is empty');
        }

        $this->firstName = $firstName;
    }

    /**
     * @param JSONValue $lastName
     **/
    public function setLastName(mixed $lastName): void
    {
        if (!(is_string($lastName))) {
            throw new HTTPException(400, 'lastName is not string');
        }

        $this->lastName = $lastName;
    }

    /**
     * @param JSONValue $email
     **/
    public function setEmail(mixed $email): void
    {
        if (!is_string($email)) {
            throw new HTTPException(400, 'email is not string');
        }

        $sanitizedEmail = filter_var($email, FILTER_SANITIZE_EMAIL);
        $validatedEmail = filter_var($sanitizedEmail, FILTER_VALIDATE_EMAIL);

        if ($validatedEmail === false) {
            throw new HTTPException(400, 'Wrong email format');
        }

        $this->email = $validatedEmail;
    }

    /**
     * @param JSONValue $rawPassword
     **/
    public function setPassword(mixed $rawPassword): void
    {
        if (!is_string($rawPassword)) {
            throw new HTTPException(400, 'password is not string');
        }

        if ($rawPassword === '') {
            throw new HTTPException(400, 'password is empty string');
        }

        $this->password = self::hashPassword($rawPassword);
    }

    /**
     * @param DateTimeImmutable | null  $dob
     **/
    public function setDob(mixed $dob): void
    {
        if ($dob > new DateTimeImmutable('now')) {
            throw new HTTPException(400, 'Date of birth cannot be later than today');
        }

        $this->dob = $dob;
    }

    /**
     * @param JSONValue $isAdmin
     **/
    public function setIsAdmin(mixed $isAdmin): void
    {
        if (!is_bool($isAdmin)) {
            throw new HTTPException(400, 'isAdmin is not bool');
        }

        $this->isAdmin = $isAdmin;
    }

    /**
     * @param JSONValue $dobString
     **/
    public function setDobFromString(mixed $dobString): void
    {
        if (!is_string($dobString) && !is_null($dobString)) {
            throw new HTTPException(400, 'Date of birth is not string or null');
        }

        if ($dobString === '' || is_null($dobString)) {
            $this->setDob(null);
            return;
        }

        try {
            $dob = new DateTimeImmutable($dobString);
        } catch (Exception $e) {
            throw new HTTPException(400, 'Wrong date of birth format');
        }
        $this->setDob($dob);
    }

    /**
     * @param JSONValue $gender
     **/
    public function setGender(mixed $gender): void
    {
        if (!is_null($gender) && $gender !== 'M' && $gender !== 'F') {
            throw new HTTPException(400, 'Wrong gender value');
        }

        $this->gender = $gender;
    }

    /**
     * @param JSONValue $newStorageUsedKb
     **/
    public function setStorageUsed(mixed $newStorageUsedKb): void
    {
        if (!is_int($newStorageUsedKb)) {
            throw new LogicException('New storage used value is not integer');
        }

        if ($newStorageUsedKb < 0) {
            $this->storageUsedKb = 0;
            return;
        }

        if ($newStorageUsedKb > $this->storageSizeKb) {
            throw new HTTPException(400, 'User total storage is used up');
        }

        $this->storageUsedKb = $newStorageUsedKb;
    }

    public function __get(string $name): string | int | bool | DateTimeImmutable | null
    {
        return match($name) {
            'email' => $this->getEmail(),
            'password' => $this->getPassword(),
            'isAdmin' => $this->getIsAdmin(),
            'dob' => $this->getDob(),
            'gender' => $this->getGender(),
            'storageUsedKb' => $this->getStorageUsed(),
            default => throw new LogicException("Tried to get property $name which does not exist"),
        };
    }

    /**
     * @param JSONValue $value
     **/
    public function __set(string $name, mixed $value): void
    {
        switch ($name) {
            case 'email':
                $this->setEmail($value);
                break;
            case 'password':
                $this->setPassword($value);
                break;
            case 'isAdmin':
                $this->setIsAdmin($value);
                break;
            case 'dob':
                $this->setDobFromString($value);
                break;
            case 'gender':
                $this->setGender($value);
                break;
            case 'gender':
                $this->setGender($value);
                break;
            case 'storageUsedKb':
                $this->setStorageUsed($value);
                break;
            default:
                throw new LogicException('Tried to set value which does not exist');
        }
    }
}
