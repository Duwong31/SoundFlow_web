<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PasswordReset  extends Model
{

    const UPDATED_AT = null;
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('auth.passwords.users.table'));
    }
    protected $fillable=['email','token'];
}
