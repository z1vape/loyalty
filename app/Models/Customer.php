<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\BelongsToStore;

class Customer extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id',
        'name',
        'phone',
        'email',
        'card_token',
        'member_code',
        'tier',
        'points',
        'liquid_stamp_count',
        'liquid_stamp_rewards_available',
        'total_spent',
        'birth_date',
        'favorite_flavor',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'total_spent' => 'decimal:2',
        'points' => 'integer',
        'liquid_stamp_count' => 'integer',
        'liquid_stamp_rewards_available' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($customer) {
            if (empty($customer->card_token)) {
                $customer->card_token = Str::random(24);
            }
            if (empty($customer->member_code)) {
                // Generate format VP-XXXXX (5 digits)
                do {
                    $code = 'VP-' . random_int(10000, 99999);
                } while (static::where('member_code', $code)->exists());
                $customer->member_code = $code;
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function loyaltyLogs()
    {
        return $this->hasMany(LoyaltyLog::class)->latest();
    }

    /**
     * Get digital card public URL
     */
    public function getCardUrlAttribute(): string
    {
        return url('/card/' . $this->card_token);
    }

    /**
     * Get WhatsApp invitation/share URL
     */
    public function getWhatsAppShareUrlAttribute(): string
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        $text = "Halo {$this->name}! 👋\n\nSelamat bergabung di Membership Z1 Vape Store!\n"
            . "Ini Kartu Member Digital kamu:\n"
            . "👉 " . $this->card_url . "\n\n"
            . "Kode Member: *{$this->member_code}*\n"
            . "Tunjukkan QR code di link tersebut setiap belanja Liquid/Device untuk dapatkan Poin & Gratisan!\n\n"
            . "Tips: Buka link di browser lalu pilih 'Add to Home Screen' agar kartu tersimpan di HP kamu. 💨";

        return 'https://api.whatsapp.com/send?phone=' . $cleanPhone . '&text=' . rawurlencode($text);
    }

    /**
     * Add points and log the transaction
     */
    public function addPoints(int $points, ?int $saleId = null, string $description = 'Perolehan Poin Belanja'): void
    {
        if ($points <= 0) return;

        $this->increment('points', $points);
        $this->refresh();

        LoyaltyLog::create([
            'customer_id' => $this->id,
            'store_id' => $this->store_id,
            'sale_id' => $saleId,
            'user_id' => auth()->id(),
            'type' => 'earn_points',
            'points_change' => $points,
            'points_balance' => $this->points,
            'stamps_change' => 0,
            'stamps_balance' => $this->liquid_stamp_count,
            'description' => $description,
        ]);
    }

    /**
     * Redeem points for discount
     */
    public function redeemPoints(int $points, ?int $saleId = null, string $description = 'Penukaran Poin Diskon'): bool
    {
        if ($points <= 0 || $this->points < $points) return false;

        $this->decrement('points', $points);
        $this->refresh();

        LoyaltyLog::create([
            'customer_id' => $this->id,
            'store_id' => $this->store_id,
            'sale_id' => $saleId,
            'user_id' => auth()->id(),
            'type' => 'redeem_points',
            'points_change' => -$points,
            'points_balance' => $this->points,
            'stamps_change' => 0,
            'stamps_balance' => $this->liquid_stamp_count,
            'description' => $description,
        ]);

        return true;
    }

    /**
     * Add liquid stamps (every 5 stamps earns 1 reward)
     */
    public function addStamps(int $count, ?int $saleId = null, string $description = 'Stempel Beli Liquid'): void
    {
        if ($count <= 0) return;

        $newStampCount = $this->liquid_stamp_count + $count;
        $newRewards = intdiv($newStampCount, 5);
        $remainingStamps = $newStampCount % 5;

        $this->liquid_stamp_count = $remainingStamps;
        if ($newRewards > 0) {
            $this->liquid_stamp_rewards_available += $newRewards;
        }
        $this->save();

        LoyaltyLog::create([
            'customer_id' => $this->id,
            'store_id' => $this->store_id,
            'sale_id' => $saleId,
            'user_id' => auth()->id(),
            'type' => 'earn_stamp',
            'points_change' => 0,
            'points_balance' => $this->points,
            'stamps_change' => $count,
            'stamps_balance' => $this->liquid_stamp_count,
            'description' => $description . ($newRewards > 0 ? " (Selamat! Anda dapat {$newRewards} hadiah stempel)" : ''),
        ]);
    }

    /**
     * Update tier based on cumulative spending
     */
    public function updateTierAfterPurchase(float $spentAmount): void
    {
        $this->total_spent += $spentAmount;

        $currentTier = $this->tier;
        $newTier = 'Rookie';

        if ($this->total_spent >= 3500000) {
            $newTier = 'Master';
        } elseif ($this->total_spent >= 1500000) {
            $newTier = 'Cloud Chaser';
        }

        $this->tier = $newTier;
        $this->save();

        if ($currentTier !== $newTier) {
            LoyaltyLog::create([
                'customer_id' => $this->id,
                'store_id' => $this->store_id,
                'type' => 'tier_upgrade',
                'description' => "Naik level ke {$newTier}! 🔥",
                'points_balance' => $this->points,
                'stamps_balance' => $this->liquid_stamp_count,
            ]);
        }
    }
}

