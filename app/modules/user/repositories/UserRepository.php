<?php
declare(strict_types=1);

namespace app\modules\user\repositories;

final class UserRepository extends Repository {

    public function getLoggedUser(string $cId = null):? array {
        $token = $this->session->get('ut');

        if (!$token) {
            return null;
        }

        $userData = $this->db->myco
        ->table('users')
        ->where('token', $token)
        ->first();

        if (!$userData || $token !== $userData->token) {
            $this->session->remove('ut');
            return null;
        }

        $userData = $this->helper->array->stdToArray($userData);

        $userData['companies'] = $this->repository('CompanyRepository')->getUserCompanies($userData['id']);

        if (!empty($cId)) {
            $currentCompany = $userData['companies'][$cId] ?? [];
            if (!empty($currentCompany['id'])) {
                $userData['currentCompany'] = $currentCompany['id'];
                $this->factory('UserFactory')->setUserCurrentCompany($userData['id'], $currentCompany['id']);
            }
        }

        if (empty($userData['currentCompany'])) {
            $currentCompany = current($userData['companies'] ?? []);
            if (!empty($currentCompany['id'])) {
                $userData['currentCompany'] = $currentCompany['id'];
                $this->factory('UserFactory')->setUserCurrentCompany($userData['id'], $currentCompany['id']);
            }
        }

        if (!empty($userData['currentCompany']) && array_key_exists($userData['currentCompany'], $userData['companies'] ?? [])) {
            $userData['currentCompany'] = $userData['companies'][$userData['currentCompany']];
        } else {
            $userData['currentCompany'] = null;
        }

        $userData['roles'] = $this->repository('RoleRepository')->getUserAllRoles($userData['id']);

        foreach (['token', 'password'] as $v) {
            unset($userData[$v]);
        }

        return $userData;
    }

    public function signin($userParams):bool {
        $userData = $this->db->myco->table('users')->where([
            ['email', '=', $userParams['email']],
            ['password', '=', md5($userParams['password'])]
        ])->first();

        if (isset($userData->secondId)) {
            $token = md5(uniqid($userData->secondId, true));
            $this->db->myco
            ->table('users')
            ->where('id', $userData->id)
            ->update(['token' => $token]);
            $this->session->set('ut', $token);
            $this->log->info('signin: '.$userData->secondId);
            $flash = $this->session->getFlash();
            $flash->add('success', 'Üdv '.$userData->firstName.'!');
            return true;
        } else {
            return false;
        }
    }

    public function signout():bool {
        $userData = $this->getLoggedUser();
        if ($this->session->has('ut')) {
            $this->db->myco
            ->table('users')
            ->where('token', $this->session->get('ut'))
            ->update(['token' => null]);
            $this->session->remove('ut');
        }
        $this->log->info('signout: '.$userData['secondId']);
        $flash = $this->session->getFlash();
        $flash->add('success', 'Sikeresen kijelentkezett');
        return true;
    }

    public function checkEmailReserved(string $email): bool {
        $user = $this->db->myco
        ->table('users')
        ->select('email')
        ->where('email', $email)
        ->first();

        if (isset($user->email)) {
            return $user->email == $email;
        }

        return false;
    }

    public function generateUserSecondId(): string {
        $maxAttempts = 100; // Maximális kísérletek száma
        $attempts = 0;

        do {
            $secondId = date("dmy") . str_pad((string)rand(0, 9999), 4, '0', STR_PAD_LEFT);

            $secondIdInDb = $this->db->myco
            ->table('users')
            ->where('secondId', $secondId)
            ->value('secondId');

            // Ellenőrizzük, hogy a másodlagos azonosító egyedi-e
            if (empty($secondIdInDb)) {
                return $secondId; // Sikeres generálás esetén kilépünk a ciklusból
            }

            $attempts++;

        } while ($attempts < $maxAttempts);

        throw new \RuntimeException('Nem sikerült egyedi másodlagos azonosítót generálni.');
    }

}
?>