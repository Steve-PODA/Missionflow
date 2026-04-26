# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Démarrer l'environnement de dev complet (Laravel + queue + pail + Vite en parallèle)
composer run dev

# Compiler les assets pour la production
npm run build

# Lancer tous les tests
php artisan test

# Lancer un test spécifique
php artisan test --filter NomDuTest

# Migrations + seed complet
php artisan migrate --seed

# Seed uniquement les rôles/permissions (après un fresh)
php artisan db:seed --class=RolesAndPermissionsSeeder

# Commandes WhatsApp
php artisan missions:remind       # Rappels J-1
php artisan missions:day-alerts   # Alertes jour J
php artisan missions:start-due    # Passe les missions dues en in_progress
php artisan whatsapp:test         # Test d'envoi manuel
```

> Prérequis : WAMP actif (MySQL sur port 3306), base `missionflow_db`, user `root` sans mot de passe.

## Architecture

### Flux de requête
```
Navigateur → Inertia.js → Controller Laravel → Inertia::render('Page', [...props])
                                                       ↓
                                               Vue 3 SPA (hydratation)
```
Pas d'API REST classique : tout passe par Inertia. Les props sont typées Laravel côté serveur et reçues directement dans le `<script setup>` Vue.

### Authentification (3 couches)
1. **Login** — email + mot de passe (`AuthenticatedSessionController`)
2. **2FA obligatoire** — middleware `RequireTwoFactor` : si `google2fa_secret` absent → redirect vers `/2fa/setup` ; si présent mais `2fa_verified` absent de la session → redirect vers `/2fa/verify`
3. **Blocage** — middleware `EnsureUserIsNotBlocked` : logout immédiat si `is_blocked = true`

L'inscription publique est **désactivée volontairement** — les comptes sont créés par l'admin via `/users` uniquement.

### Rôles & Permissions (Spatie)
| Rôle | Ce qu'il peut faire |
|------|---------------------|
| `admin` | Tout, y compris gestion users, activity log, WhatsApp |
| `manager` | Missions (CRUD + statut) + personnel |
| `agent` | Voir et mettre à jour le statut de **ses** missions uniquement |

Les permissions sont partagées vers Vue via `HandleInertiaRequests::share()` sous `auth.can.*` (ex: `auth.can.edit_missions`). Toujours vérifier ces flags avant d'afficher un bouton d'action dans les composants Vue.

Le filtrage côté agent se fait systématiquement avec `whereHas('users', fn($q) => $q->where('users.id', $authUser->id))`. Vérifier `$authUser->hasRole('agent')` dans chaque contrôleur qui liste des données.

### Modèles & relations clés
- `Mission` ↔ `User` : many-to-many via le pivot **`mission_user`** (pas de colonnes supplémentaires sur le pivot)
- `Mission::$fillable` : `title`, `briefing`, `company`, `date`, `start_time`, `duration`, `location`, `priority`, `client_name`, `client_email`, `client_phone`, `status`
- Statuts mission : `pending` → `in_progress` → `completed` / `cancelled`
- `MissionController::index()` auto-transite les missions `pending` dont la date/heure est dépassée vers `in_progress` à chaque visite de la liste
- Les deux modèles ont `LogsActivity` (Spatie) — `logOnlyDirty()`, uniquement les champs métier ciblés

### Convention contrôleurs
Toujours annoter l'utilisateur authentifié pour que l'IDE reconnaisse `hasRole()` et `can()` :
```php
/** @var \App\Models\User $authUser */
$authUser = Auth::user();
```

### WhatsApp (Meta Cloud API)
- Service : `app/Services/WhatsAppService.php` — `sendTemplate()` pour les alertes planifiées, `sendText()` uniquement dans une fenêtre de 24h après message entrant
- Templates approuvés Meta : `mission_reminder_v1` (rappel J-1, 5 params), `mission_day_alert` (jour J, 4 params)
- En mode développement : token WHATSAPP_TOKEN expire toutes les 24h — à renouveler manuellement dans Meta API Setup
- **Prochaine étape** : passer en Live mode Meta Business pour lever la restriction des numéros autorisés et accéder au quota gratuit (1 000 msg/mois)

### Particularités WAMP / MySQL
`AppServiceProvider::boot()` applique `Schema::defaultStringLength(191)` pour éviter l'erreur "clé trop longue" sur MySQL sans `utf8mb4` complet.
