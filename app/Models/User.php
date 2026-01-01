<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Traits\HasRoles;
use Symfony\Component\VarDumper\VarDumper;
use phpseclib3\Crypt\DES;

class User extends Authenticatable
{
    use HasRoles;
    protected $connection = 'mysql_user';
    protected $table = 'velox_main.users';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $guard_name = 'web';

    protected $fillable = ['UserID', 'Name', 'P_Enc', 'P_Enc2'];

    public function getAuthIdentifierName()
    {
        return $this->getKeyName(); // ID
    }

    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    public function getAuthPassword()
    {
        return $this->P_Enc;
    }

    public function getRememberToken()
    {
        return $this->{$this->getRememberTokenName()} ?? null;
    }

    public function setRememberToken($value)
    {
        $this->{$this->getRememberTokenName()} = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    private static function encryptString($str, $key)
    {
        $key = substr(str_pad($key, 8, "\0"), 0, 8);

        $des = new DES('ecb');
        $des->setKey($key);
        $des->enablePadding();

        return $des->encrypt($str);
    }

    private static function decryptString($encrypted, $key)
    {
        $key = substr(str_pad($key, 8, "\0"), 0, 8);

        $des = new DES('ecb');
        $des->setKey($key);
        $des->enablePadding();

        return $des->decrypt($encrypted);
    }

    /** Hashing sesuai sistem lama (match Node.js 100%) */
    public static function hashOldPassword($password)
    {
        $key = 'A2918991'; // harus sama dengan Node.js

        // Encrypt DES-ECB dan hasilnya raw binary
        $passz = self::encryptString($password, $key);
        if ($passz === null) {
            return null;
        }

        // MD5(passz)  → hex string
        $pMD5 = md5($passz); // php md5($binary) sudah menghasilkan hex lowercase

        // SHA1(pMD5) → hex string
        $pSHA = sha1($pMD5);

        // MD5(pSHA)  → hex string (hasil akhir)
        $pMD5_2 = md5($pSHA);

        return $pMD5_2;
    }

    /** Ambil user by username */
    public static function findByUserID($username)
    {
        return self::where('UserID', $username)->first();
    }

    /** Validasi login (business logic ringan masih boleh di model) */
    public static function verify($username, $password)
    {
        $key = 'A2918991';
        $user = self::findByUserID($username);
        if (!$user) {
            return null;
        }

        $hashed = self::hashOldPassword($password);

        $stored1 = trim($user->P_Enc);
        $stored2 = trim($user->P_Enc2);
        $calc = trim($hashed);

        if (!hash_equals($stored1, $calc) && !hash_equals($stored2, $calc)) {
            return null;
        }

        return $user;
    }

    /**
     * Override default roles relationship to use vl_users table
     */
    // public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    // {
    //     // Specify the pivot table with database name if needed, assuming 'vl_users' is in the default connection
    //     // Since User is in 'mysql_user', we need to be explicit if vl_users is in 'mysql'
    //     $pivotTable = config('database.connections.mysql.database') ? config('database.connections.mysql.database') . '.vl_users' : 'vl_users';

    //     return $this->belongsToMany(\App\Models\Role::class, $pivotTable, 'user_l_ID', 'role_id', 'ID', 'id');
    // }
}
