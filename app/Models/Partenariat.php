<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partenariat extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_entreprise',
        'contact',
        'email',
        'telephone',
        'site_web',
        'type_partenariat',
        'budget_envisage',
        'message',
        'status',
        'notes_internes',
        'ip_demandeur',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const STATUS_NOUVEAU = 'nouveau';
    public const STATUS_EN_COURS = 'en_cours';
    public const STATUS_ACCEPTE = 'accepte';
    public const STATUS_REFUSE = 'refuse';

    public const TYPES = [
        'sponsoring' => 'Sponsoring d\'épisode',
        'collaboration' => 'Collaboration de contenu',
        'produit' => 'Placement de produit',
        'evenement' => 'Partenariat événementiel',
        'affiliation' => 'Programme d\'affiliation',
        'echange' => 'Échange de services',
        'autre' => 'Autre proposition',
    ];

    public const BUDGETS = [
        'moins_1000' => 'Moins de 1 000 €',
        '1000_5000' => '1 000 € – 5 000 €',
        '5000_10000' => '5 000 € – 10 000 €',
        '10000_25000' => '10 000 € – 25 000 €',
        'plus_25000' => 'Plus de 25 000 €',
        'negociable' => 'À négocier',
        'echange' => 'Échange de services',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_NOUVEAU => 'Nouveau',
            self::STATUS_EN_COURS => 'En cours',
            self::STATUS_ACCEPTE => 'Accepté',
            self::STATUS_REFUSE => 'Refusé',
        ];
    }

    public function scopeNouveau(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NOUVEAU);
    }

    public function scopeEnCours(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_EN_COURS);
    }

    public function scopeAccepte(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACCEPTE);
    }

    public function scopeRefuse(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REFUSE);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_NOUVEAU, self::STATUS_EN_COURS]);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nom_entreprise', 'like', "%{$search}%")
              ->orWhere('contact', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('message', 'like', "%{$search}%");
        });
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type_partenariat] ?? $this->type_partenariat;
    }

    public function getBudgetLabelAttribute(): string
    {
        return self::BUDGETS[$this->budget_envisage] ?? $this->budget_envisage;
    }

    public function getIsNouveauAttribute(): bool
    {
        return $this->status === self::STATUS_NOUVEAU;
    }

    public function getIsEnCoursAttribute(): bool
    {
        return $this->status === self::STATUS_EN_COURS;
    }

    public function getIsAccepteAttribute(): bool
    {
        return $this->status === self::STATUS_ACCEPTE;
    }

    public function getIsRefuseAttribute(): bool
    {
        return $this->status === self::STATUS_REFUSE;
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_NOUVEAU, self::STATUS_EN_COURS], true);
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = strtolower(trim((string) $value));
    }

    public function setNomEntrepriseAttribute($value): void
    {
        $this->attributes['nom_entreprise'] = trim((string) $value);
    }

    public function setContactAttribute($value): void
    {
        $this->attributes['contact'] = trim((string) $value);
    }

    public function marquerEnCours(?string $notes = null): bool
    {
        $this->status = self::STATUS_EN_COURS;
        if ($notes) {
            $this->notes_internes = $notes;
        }
        return $this->save();
    }

    public function accepter(?string $notes = null): bool
    {
        $this->status = self::STATUS_ACCEPTE;
        if ($notes) {
            $this->notes_internes = $notes;
        }
        return $this->save();
    }

    public function refuser(?string $notes = null): bool
    {
        $this->status = self::STATUS_REFUSE;
        if ($notes) {
            $this->notes_internes = $notes;
        }
        return $this->save();
    }

    public static function countByStatus(): array
    {
        return [
            self::STATUS_NOUVEAU => static::nouveau()->count(),
            self::STATUS_EN_COURS => static::enCours()->count(),
            self::STATUS_ACCEPTE => static::accepte()->count(),
            self::STATUS_REFUSE => static::refuse()->count(),
        ];
    }

    public static function getNewCount(): int
    {
        return static::nouveau()->count();
    }

    public static function getActiveCount(): int
    {
        return static::active()->count();
    }
}
