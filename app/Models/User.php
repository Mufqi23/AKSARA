<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * In-memory cache of preloaded encrypted/plain password settings per user+hash.
     *
     * @var array<string, string|null>
     */
    protected static array $preloadedVisiblePasswords = [];

    /**
     * Memoized demo-password hash checks so user listing pages never repeat Bcrypt per row.
     *
     * @var array<string, bool>
     */
    protected static array $demoHashMatches = [];

    protected $fillable = [
        'name', 'email', 'password', 'role', 'status', 'email_verified_at', 'profile_picture',
        'two_factor_enabled', 'two_factor_code', 'two_factor_expires_at',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_code', 'two_factor_expires_at'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_expires_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Batch-preload admin-visible passwords for a collection of users in a single SQL query.
     *
     * @param  iterable<int, self>  $users
     */
    public static function preloadAdminVisiblePasswords(iterable $users): void
    {
        $items = [];
        $keys = [];
        foreach ($users as $user) {
            if ($user instanceof self && $user->id && $user->role !== 'admin') {
                $items[] = $user;
                $keys[] = 'user_pass_'.$user->id;
            }
        }

        if ($items === []) {
            return;
        }

        $storedMap = Setting::whereIn('key', array_values(array_unique($keys)))
            ->pluck('value', 'key')
            ->all();

        foreach ($items as $user) {
            $cacheKey = $user->id.':'.$user->password;
            $raw = $storedMap['user_pass_'.$user->id] ?? null;
            self::$preloadedVisiblePasswords[$cacheKey] = self::decodeVisiblePassword($raw);
        }
    }

    public function adminVisiblePassword(): ?string
    {
        if (! $this->id || ! $this->password || $this->role === 'admin') {
            return null;
        }

        $cacheKey = $this->id.':'.$this->password;
        if (array_key_exists($cacheKey, self::$preloadedVisiblePasswords)) {
            $cached = self::$preloadedVisiblePasswords[$cacheKey];
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        } else {
            $stored = Setting::where('key', 'user_pass_'.$this->id)->value('value');
            $decoded = self::decodeVisiblePassword($stored);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        $isDemo = self::$demoHashMatches[$this->password]
            ??= Hash::check('CampusDemo!2026', $this->password);

        return $isDemo ? 'CampusDemo!2026' : null;
    }

    public function saveAdminVisiblePassword(string $plainPassword): void
    {
        if (! $this->id || $plainPassword === '' || $this->role === 'admin') {
            return;
        }

        Setting::updateOrCreate(
            ['key' => 'user_pass_'.$this->id],
            ['value' => Crypt::encryptString($plainPassword)]
        );

        if ($this->password) {
            self::$preloadedVisiblePasswords[$this->id.':'.$this->password] = $plainPassword;
        }
    }

    private static function decodeVisiblePassword(mixed $stored): ?string
    {
        if (! is_string($stored) || $stored === '') {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            return $stored;
        }
    }
}
