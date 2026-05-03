<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterAbonne extends Model
{
    use HasFactory;

    protected $table = 'newsletter_abonnes';

    protected $fillable = [
        'email',
        'prenom',
        'nom',
        'date_inscription',
        'date_confirmation',
        'date_desinscription',
        'status',
        'confirme',
        'token_desabonnement',
        'source_inscription',
        'frequence_envoi',
        'interets',
        'ip_inscription',
        'raison_desinscription',
        'commentaire_desinscription',
    ];

    protected $casts = [
        'date_inscription' => 'datetime',
        'date_confirmation' => 'datetime',
        'date_desinscription' => 'datetime',
        'confirme' => 'boolean',
        'interets' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const STATUS_ACTIF = 'actif';
    public const STATUS_INACTIF = 'inactif';
    public const STATUS_DESABONNE = 'desabonne';

    public const FREQUENCES = ['hebdomadaire', 'bihebdomadaire', 'mensuel'];

    public const INTERETS = [
        'cuisine', 'menage', 'organisation', 'beaute', 'technologie', 'lifestyle',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIF => 'Actif',
            self::STATUS_INACTIF => 'En attente de confirmation',
            self::STATUS_DESABONNE => 'Désabonné',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $abonne) {
            if (empty($abonne->token_desabonnement)) {
                $abonne->token_desabonnement = self::generateUniqueToken();
            }
            if (empty($abonne->date_inscription)) {
                $abonne->date_inscription = now();
            }
        });
    }

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIF);
    }

    public function scopeInactif(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INACTIF);
    }

    public function scopeDesabonne(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DESABONNE);
    }

    public function scopeConfirme(Builder $query): Builder
    {
        return $query->where('confirme', true);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('date_inscription', 'desc');
    }

    public function scopeAbonnesActifs(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIF)->where('confirme', true);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getIsActifAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIF;
    }

    public function getIsInactifAttribute(): bool
    {
        return $this->status === self::STATUS_INACTIF;
    }

    public function getIsDesabonneAttribute(): bool
    {
        return $this->status === self::STATUS_DESABONNE;
    }

    public function getPrenomCompletAttribute(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? '')) ?: $this->email;
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = strtolower(trim((string) $value));
    }

    public function confirmer(): bool
    {
        $this->confirme = true;
        $this->date_confirmation = now();
        $this->status = self::STATUS_ACTIF;
        return $this->save();
    }

    public function desabonner(?string $raison = null, ?string $commentaire = null): bool
    {
        $this->status = self::STATUS_DESABONNE;
        $this->date_desinscription = now();
        $this->raison_desinscription = $raison;
        $this->commentaire_desinscription = $commentaire;
        return $this->save();
    }

    public function genererNouveauToken(): string
    {
        $this->token_desabonnement = self::generateUniqueToken();
        $this->save();
        return $this->token_desabonnement;
    }

    public static function findByToken(string $token): ?self
    {
        return static::where('token_desabonnement', $token)->first();
    }

    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(60);
        } while (static::where('token_desabonnement', $token)->exists());

        return $token;
    }

    public static function countByStatus(): array
    {
        return [
            self::STATUS_ACTIF => static::actif()->count(),
            self::STATUS_INACTIF => static::inactif()->count(),
            self::STATUS_DESABONNE => static::desabonne()->count(),
        ];
    }

    public static function getActiveCount(): int
    {
        return static::abonnesActifs()->count();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}
