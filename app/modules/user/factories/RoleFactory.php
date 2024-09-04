<?php
declare(strict_types=1);

namespace app\modules\user\factories;

final class RoleFactory extends Factory {

    public function addUserRoles(int $userId, $addRoles)/*: bool*/ {
        $addRoles = is_array($addRoles) ? $addRoles : [$addRoles];

        $rolesDbArray = $this->repository('RoleRepository')->convertRolesToDbIdArray($userId, $addRoles);

        $this->db->myco
        ->table('userRoles')
        ->insert($rolesDbArray);

        return true;
    }

    
    /*
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
    */
}
?>