<?php

namespace WHMCS\Module\Addon\Realname;

use WHMCS\Model\AbstractModel;

/**
 * Convenience model for custom modules
 */

class ClientModel extends AbstractModel
{
    protected $table = 'mod_realname_clients';
    protected $primaryKey = 'id';

    protected $fillable = [
        'client_id',
        'name',
        'id_card',
        'verified_at',
    ];
}