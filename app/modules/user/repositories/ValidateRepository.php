<?php
declare(strict_types=1);

namespace app\modules\user\repositories;

use \Respect\Validation\Validator as v;
use \Respect\Validation\Exceptions\NestedValidationException;

final class ValidateRepository extends Repository {

    private $data;

    public function validate(array $data): array {
        $data = array_intersect_key($data, array_flip([
            'lastName',
            'firstName',
            'email',
            'password',
            'passwordConfirm'
        ]));

        $errors = [];
        foreach ($data as $key => $value) {
            $errors[$key] = $this->$key($value);
        }

        return array_filter($errors);
    }

    private function lastName($data):? string {
        try {
            v::allOf(
                v::regex('/^[a-zA-ZáÁéÉíÍóÓöÖőŐúÚüÜűŰ .-]*$/'),
                v::length(3, 20),
                v::NotEmpty()
            )->assert($data);
            return null;
        } catch(NestedValidationException $e) {
            return current($e->getMessages([
                'notEmpty'  => 'A vezetéknév nem lehet üres',
                'length'    => 'A vezetéknév 3-20 karakter lehet',
                'regex'     => 'A vezetéknév tiltott karaktert tartalmaz'
            ]));
        }
    }

    private function firstName($data):? string {
        try {
            v::allOf(
                v::regex('/^[a-zA-ZáÁéÉíÍóÓöÖőŐúÚüÜűŰ .-]*$/'),
                v::length(3, 20),
                v::NotEmpty()
            )->assert($data);
            return null;
        } catch(NestedValidationException $e) {
            return current($e->getMessages([
                'notEmpty'  => 'A keresztnév nem lehet üres',
                'length'    => 'A keresztnév 3-20 karakter lehet',
                'regex'     => 'A keresztnév tiltott karaktert tartalmaz'
            ]));
        }
    }

    private function email($data):? string {
        try {
            v::allOf(
                v::email(),
                v::length(5, 50),
                v::NotEmpty()
            )->assert($data);

            $emailIsReserved = $this->repository('UserRepository')->checkEmailReserved($data);

            if ($emailIsReserved) {
                return 'Az e-mail cím foglalt';
            }

            return null;
        } catch(NestedValidationException $e) {
            return current($e->getMessages([
                'notEmpty'  => 'Az e-mail cím nem lehet üres',
                'length'    => 'Az e-mail cím 5-50 karakter lehet',
                'email'     => 'Az e-mail cím nem megfelelő'
            ]));
        }
    }

    private function password($data):? string {
        try {
            v::allOf(
                v::regex('/^\S*(?=\S{8,})(?=\S*\p{Ll})(?=\S*\p{Lu})(?=\S*[\d])\S*$/u'),
                v::NotEmpty()
            )->assert($data);

            $this->data['password'] = $data;
            return null;
        } catch(NestedValidationException $e) {
            $this->data['password'] = null;
            return current($e->getMessages([
                'notEmpty'  => 'A jelszó nem lehet üres',
                'regex'     => 'A jelszó nem biztonságos'
            ]));
        }
    }

    private function passwordConfirm($data):? string {
        return (!empty($this->data['password']) && $this->data['password'] != $data) ? 'A jelszavak nem egyeznek' : null;
    }
}
?>