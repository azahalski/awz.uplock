<?php
namespace Awz\Uplock\Access\Tables;

use Bitrix\Main\Access\Role\AccessRoleRelationTable;

class RoleRelationTable extends AccessRoleRelationTable
{
    public static function getTableName()
    {
        return 'awz_uplock_role_relation';
    }

}