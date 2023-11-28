<?php

declare(strict_types=1);

namespace Models\User;

use DateTimeImmutable;
use Kernel\Models\DataMapper\Database\DBData;
use Kernel\Models\DataMapper\Database\DBDataMapper;
use RuntimeException;

/**
 * @extends DBDataMapper<User, array<string, DBData>>
 **/
class UserMapper extends DBDataMapper
{
    /**
     * @param User $user
     * @return array<string, DBData>
     **/
    protected function mapObjectToArray(object $user): array
    {
        $valuesArray = [
            'first_name' => $user->firstName,
            'last_name' => $user->lastName,
            'email' => $user->email,
            'password' => $user->password,
            'is_admin' => $user->isAdmin,
            'dob' => $user->dob,
            'gender' => $user->gender,
            'password_reset_token' => $user->passwordResetToken,
            'password_reset_token_expiration' => $user->passwordResetTokenExpiration,
            'email_verification_token' => $user->emailVerificationToken,
            'email_verification_token_expiration' => $user->emailVerificationTokenExpiration,
            'is_email_verified' => $user->isEmailVerified,
            'root_folder_id' => $user->rootFolderId,
            'storage_used_kb' => $user->storageUsedKb,
            'storage_size_kb' => $user->storageSizeKb,
        ];

        return array_map(fn ($value) => new DBData($value), $valuesArray);
    }

    /**
     * @param array<string, DBData> $data
     * @return User $object
     **/
    protected function mapArrayToObject(array $data): User
    {
        $genderValue = $data['gender']-> value;
        if ($genderValue !== 'M' && $genderValue !== 'F' && $genderValue !== null) {
            throw new RuntimeException('Got wrong gender value from database');
        }

        $id = $data['id']->value;
        $firstName = $data['first_name']->value;
        $lastName = $data['last_name']->value;
        $email = $data['email']->value;
        $password = $data['password']->value;
        $dob = $data['dob']->value;
        $passwordResetToken = $data['password_reset_token']->value;
        $passwordResetTokenExpiration = $data['password_reset_token_expiration']->value;
        $emailVerificationToken = $data['email_verification_token']->value;
        $emailVerificationTokenExpiration = $data['email_verification_token_expiration']->value;
        $rootFolderId = $data['root_folder_id']->value;
        $storageUsedKb = $data['storage_used_kb']->value;
        $storageSizeKb = $data['storage_size_kb']->value;

        if (
            !is_numeric($id) ||
            !is_string($firstName) ||
            !is_string($lastName) ||
            !is_string($email) ||
            !is_string($password) ||
            !is_string($dob) ||
            !(
                is_string($passwordResetToken) ||
                is_null($passwordResetToken)
            ) ||
            !(
                is_string($emailVerificationToken) ||
                is_null($emailVerificationToken)
            ) ||
            !(
                is_string($passwordResetTokenExpiration) ||
                is_null($passwordResetTokenExpiration)
            ) ||
            !(
                is_string($emailVerificationTokenExpiration) ||
                is_null($emailVerificationTokenExpiration)
            ) ||
            !is_numeric($rootFolderId) ||
            !is_numeric($storageUsedKb) ||
            !is_numeric($storageSizeKb)
        ) {
            throw new RuntimeException('Got wrong data type from database');
        }

        if ($firstName === '') {
            throw new RuntimeException('First name is empty');
        }

        return new User(
            (int) $id,
            $firstName,
            $lastName,
            $email,
            $password,
            (bool) $data['is_admin']->value,
            new DateTimeImmutable($dob),
            $genderValue,
            $passwordResetToken,
            !is_null($passwordResetTokenExpiration)
            ? new DateTimeImmutable($passwordResetTokenExpiration)
            : null,
            $emailVerificationToken,
            !is_null($emailVerificationTokenExpiration)
            ? new DateTimeImmutable($emailVerificationTokenExpiration)
            : null,
            (bool) $data['is_email_verified']->value,
            (int) $rootFolderId,
            (int) $storageUsedKb,
            (int) $storageSizeKb
        );
    }
}
