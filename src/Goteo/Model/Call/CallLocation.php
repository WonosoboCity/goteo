<?php
/*
 * This file is part of the Goteo Package.
 *
 * (c) Platoniq y Fundación Goteo <fundacion@goteo.org>
 *
 * For the full copyright and license information, please view the README.md
 * and LICENSE files that was distributed with this source code.
 */

namespace Goteo\Model\Call;

use Goteo\Model\Call;
use Goteo\Model\User;

class CallLocation extends \Goteo\Model\Location\LocationItem {
    protected $Table = 'call_location';
    protected static $Table_static = 'call_location';
    public $call;

    public function __construct() {
        $args = func_get_args();
        call_user_func_array(array('parent', '__construct'), $args);
        $this->call = $this->id;
    }

    public static function get($call) {
        $id = $call;
        if($call instanceOf Call) {
            $id = $call->id;
        }

        return parent::get($id);
    }

    /**
     * Same permissions as view call
     * Onwer can view location
     * admins too
     * if call is pubic too
     */
    public function userCanView(User $user) {
        return $this->getModel()->userCanView($user);
    }

    /**
     * same permissions as edit call
     */
    public function userCanEdit(User $user) {
        return $this->getModel()->userCanEdit($user);
    }

}

