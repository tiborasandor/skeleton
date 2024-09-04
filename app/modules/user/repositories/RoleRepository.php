<?php
declare(strict_types=1);

namespace app\modules\user\repositories;

final class RoleRepository extends Repository {

    private $userRoles;
    private $roles;

    public function checkRole($roles, $loggedUser = null): bool {
        $roles = is_array($roles) ? $roles : [$roles];

        if ($loggedUser === null) {
            $loggedUser = $this->repository('UserRepository')->getLoggedUser();
        }

        foreach ($roles as $role) {
            try {
                $bool = in_array($role, $loggedUser['roles']);
            } catch (\Throwable $th) {
                $bool = false;
            }

            if ($bool) {
                return true;
            }
        }

        return false;
    }

    public function getUserAllRoles(int $userId): array {
        $roles = $this->getUserRoles($userId);
        return $this->convertRolesArrayToStringArray($roles);
    }

    public function getUserCompanyRoles(int $userId): array {
        $roles = $this->getUserRolesWhereType($userId, 'company');
        return $this->convertRolesArrayToStringArray($roles);
    }

    private function convertRolesArrayToStringArray(array $roles): array {
        array_walk($roles, function(&$role, $key) {
            switch ($role['type']) {
                case 'company':
                    $role = implode('::', [$role['type'],$role['name'],$role['companyId']]);
                break;
                default:
                    $role = implode('::', [$role['type'],$role['name']]);
                break;
            }
        });

        return $roles;
    }

    private function convertRolesStringArrayToArray(array $roles): array {
        $roles = array_flip($roles);

        array_walk($roles, function(&$value, $role) {
            list($roleType, $roleName, $connectTypeId) = explode('::', $role)+[null];
            
            $value = [
                'name' => $roleName,
                'type' => $roleType,
            ];

            if (!empty($connectTypeId)) {
                switch ($roleType) {
                    // ha kell itt lehet extra szabályokkal kiegészíteni
                    default: $value[$roleType.'Id'] = intval($connectTypeId); break;
                }
            }
        });

        return $roles;
    }

    private function getRoleAndTypeId(array $roles, $userId): array {
        $rolesWithId = $this->getRolesWithId();

        $roles = $this->convertRolesStringArrayToArray($roles);

        foreach ($roles as &$roleArray) {
            $filtered = $this->filterRolesWithId($rolesWithId, $roleArray['type'], $roleArray['name']);

            if (empty($filtered)) {
                throw new \RuntimeException('ROLE_ERROR');
            }

            $roleArray['roleId'] = $filtered['roleId'];
            $roleArray['userId'] = $userId;

            unset($roleArray['type']);
            unset($roleArray['name']);
        }

        return $roles;
    }

    private function filterRolesWithId(array $rolesWithId, string $type, string $name): ?array {
        $filtered = $this->helper->array->multiSearch($rolesWithId, 'type', $type);
        $filtered = $this->helper->array->multiSearch($filtered, 'name', $name);

        return current($filtered);
    }


    public function convertRolesToDbIdArray(int $userId, array $addRoles): array {
        $userRoles = $this->getUserAllRoles($userId);
        $addRoles = $this->helper->array->diff($addRoles, $userRoles)['normal'];
        $addRoles = $this->getRoleAndTypeId($addRoles, $userId);
        
        return array_values($addRoles);
    }

    private function getUserRolesWhereType(int $userId, string $roleType): array {
        $roles = $this->getUserRoles($userId);
        return $this->helper->array->multiSearch($roles, 'type', $roleType);
    }

    private function getUserRoles(int $userId): array {
        if (empty($this->userRoles[$userId])) {
            $roles = $this->db->myco
            ->table('userRoles')
            ->select('roles.name','roleTypes.name AS type', 'userRoles.companyId')
            ->join('roles', 'roles.id', '=', 'userRoles.roleId')
            ->join('roleTypes', 'roleTypes.id', '=', 'roles.typeId')
            ->where('userRoles.userId', $userId)
            ->get();

            $this->userRoles[$userId] = $this->helper->array->stdToArray($roles);
        }

        return $this->userRoles[$userId];
    }

    private function getRolesWithId(): array {
        if (empty($this->roles)) {
            $roles = $this->db->myco
            ->table('roles')
            ->select('roles.name', 'roles.id AS roleId','roleTypes.name AS type','roleTypes.id AS typeId')
            ->join('roleTypes', 'roleTypes.id', '=', 'roles.typeId')
            ->get();

            $this->roles = $this->helper->array->stdToArray($roles);
        }

        return $this->roles;
    }

}
?>