<?php

namespace WHMCS\Module\Addon\Realname;

use WHMCS\Model\AbstractModel;

/**
 * Convenience model for custom modules
 */

class PendingModel extends AbstractModel
{
    protected $table = 'mod_realname_pending';
    protected $primaryKey = 'id';

    protected $fillable = [
        'client_id',
        'name',
        'id_card',
        'key',
    ];

}