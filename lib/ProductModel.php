<?php

namespace WHMCS\Module\Addon\Realname;

use WHMCS\Model\AbstractModel;

/**
 * Convenience model for custom modules
 */

class ProductModel extends AbstractModel
{
    protected $table = 'mod_realname_products';
    protected $primaryKey = 'id';

    protected $fillable = [
        'product_id',
    ];
}