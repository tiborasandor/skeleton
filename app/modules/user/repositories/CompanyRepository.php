<?php
declare(strict_types=1);

namespace app\modules\user\repositories;

final class CompanyRepository extends Repository {

    public function getUserCompanies(int $userId):? array {
        $allCompanyId = $this->getUserAllCompanyId($userId);

        if (empty($allCompanyId)) {
            return null;
        }

        return $this->repository('company/CompanyRepository')->getCompanies($allCompanyId);
    }

    public function getUserAllCompanyId(int $userId): array {
        $userCompanyRoles = $this->repository('RoleRepository')->getUserCompanyRoles($userId);

        return array_map(function($userCompanyRole) {
            return intval(explode('::', $userCompanyRole)[2]);
        }, $userCompanyRoles) ?? [];
    }
}
?>