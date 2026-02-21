<?php

namespace Modules\Auth\Services;

use Modules\Auth\Enums\VerificationActionType;
use Modules\Auth\Enums\ContactType;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class VerificationCodeService
{

    public function getCacheKey( string $contact, VerificationActionType $actionType, ContactType $contactType ): string
    {
        $contact = hash('sha256', $contact . $contactType->value . $actionType->value);
        return "verification_code_{$contact}";
    }

    public function generateCode( string $contact, VerificationActionType $actionType, ContactType $contactType, ?int $expiredAt = null ): int
    {
        if( $expiredAt === null ){
            $expiredAt = $contactType === ContactType::EMAIL ? 5 : 1; // 5 minutes for email, 1 minute for phone
        }

        $code = random_int(10000, 99999);

        $cacheKey = $this->getCacheKey($contact, $actionType, $contactType);

        $expiresAt = now()->addMinutes($expiredAt);
        
        Cache::store('file')->put($cacheKey, [
            'code' => $code,
            'expired_at' => $expiresAt,
        ], $expiresAt);

        return $code;
    }

    public function getRetryTime( string $contact, ContactType $contactType, VerificationActionType $actionType ): float
    {
        $cacheKey = $this->getCacheKey($contact, $actionType, $contactType);
        $cachedData = Cache::store('file')->get($cacheKey);

        if( $cachedData && isset($cachedData['expired_at']) ) {
            $expiresAt = $cachedData['expired_at'];
            
            // If it's already a Carbon instance, use it directly
            if( $expiresAt instanceof Carbon ) {
                // Calculate remaining minutes until expiration
                // If expiresAt is in the future, this returns positive
                $remainingMinutes = now()->diffInMinutes($expiresAt);
                
                // Return the remaining minutes (0 if already expired)
                return max(0, $remainingMinutes);
            }
        }

        return 0;
    }
}
