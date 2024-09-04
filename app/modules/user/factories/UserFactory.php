<?php
declare(strict_types=1);

namespace app\modules\user\factories;

final class UserFactory extends Factory {

    public function registration(array $userData): int {
        $userData['secondId'] = $this->repository('UserRepository')->generateUserSecondId();
        $userData['password'] = md5($userData['password']);
        unset($userData['passwordConfirm']);

        $userId = $this->db->myco
        ->table('users')
        ->insertGetId($userData);

        $this->log->info('registered: '.$userData['secondId']);

        return $userId;
    }

    public function setUserCurrentCompany(int $userId, int $companyId): int {
        return $this->db->myco
        ->table('users')
        ->where('id', $userId)
        ->update(['currentCompany' => $companyId]);
    }
}
?>