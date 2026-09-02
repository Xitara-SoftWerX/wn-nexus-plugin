<?php

namespace Xitara\Nexus\Classes;

use Backend\Models\User;
use Carbon\Carbon;
use DB;
use Event;
use Log;
use Schema;
use SystemException;
use Throwable;

class BackendUserPurger
{
    public const RETENTION_DAYS = 14;
    public const REQUESTED_AT_COLUMN = 'nexus_deletion_requested_at';

    public static function requestDeletion(User $user): void
    {
        if (!static::isAvailable()) {
            throw new SystemException(
                'The Nexus backend-user deletion migration has not been applied.',
            );
        }

        DB::transaction(function () use ($user): void {
            Event::fire('backend.user.beforeDelete', [$user]);

            $user->{static::REQUESTED_AT_COLUMN} = Carbon::now();
            $user->save();
            $user->delete();
        });
    }

    public static function purgeExpired(?Carbon $now = null): int
    {
        if (!static::isAvailable()) {
            return 0;
        }

        $cutoff = static::expirationCutoff($now);
        $purged = 0;

        User::onlyTrashed()
            ->whereNotNull(static::REQUESTED_AT_COLUMN)
            ->where(static::REQUESTED_AT_COLUMN, '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($users) use (&$purged): void {
                foreach ($users as $user) {
                    try {
                        $user->forceDelete();
                        ++$purged;
                    } catch (Throwable $exception) {
                        Log::error('Unable to purge an expired Nexus backend user', [
                            'user_id' => $user->getKey(),
                            'exception' => $exception,
                        ]);
                    }
                }
            });

        return $purged;
    }

    public static function expirationCutoff(?Carbon $now = null): Carbon
    {
        return ($now ? $now->copy() : Carbon::now())->subDays(static::RETENTION_DAYS);
    }

    public static function isAvailable(): bool
    {
        return Schema::hasTable('backend_users') &&
            Schema::hasColumn('backend_users', static::REQUESTED_AT_COLUMN);
    }
}
