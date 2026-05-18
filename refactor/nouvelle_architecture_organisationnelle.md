# Proposition de Refonte de l'Architecture Organisationnelle

Ce document détaille la nouvelle structure logique de la base de données et des modèles pour répondre aux besoins d'organisation hiérarchique et de flexibilité dans l'attribution des missions.

## 1. Hiérarchie et Rôles des Utilisateurs

### Les Rôles
Nous devons distinguer les rôles "Globaux" (sans affiliation) des rôles "Opérationnels" (affiliés).
1. **Direction (Non affiliés)** : Commandant, Commandant Adjoint, Secrétaire.
2. **Opérationnels (Affiliés)** : Compel (Commandant de Peloton), Chef de Groupe, Chef d'Équipe, Équipier/Membre.

### L'Arborescence
La hiérarchie suit ce schéma :
**Peloton**  >  **Sous-groupe (Groupe)**  >  **Équipe**

*Remarque sur le Compel :* Bien que souvent lié au Peloton entier, si chaque Sous-groupe possède son propre "Compel" selon votre fonctionnement, nous le lierons au Sous-groupe. Sinon, le Compel est lié au Peloton, le Chef de Groupe au Sous-groupe, et le Chef d'Équipe à l'Équipe.

## 2. Structure de la Base de Données (Schéma)

Pour modéliser cela proprement tout en facilitant les requêtes, voici la structure recommandée :

### Table `pelotons`
- `id`
- `nom` (ex: "Peloton A")

### Table `groupes` (Les sous-groupes)
- `id`
- `nom` (ex: "Groupe 1")
- `peloton_id` (Clé étrangère vers `pelotons`)

### Table `equipes`
- `id`
- `nom` (ex: "Équipe Alpha")
- `groupe_id` (Clé étrangère vers `groupes`)

### Table `users` (Utilisateurs)
On ajoute des colonnes pour définir précisément le rôle et l'affiliation de chaque utilisateur.
- `id`, `nom`, `email`, etc.
- `role` (enum: 'commandant', 'adjoint', 'secretaire', 'compel', 'chef_groupe', 'chef_equipe', 'equipier')
- `peloton_id` (Nullable)
- `groupe_id` (Nullable)
- `equipe_id` (Nullable)

**Logique d'affiliation :**
- *Commandant / Adjoint / Secrétaire* : `peloton_id`, `groupe_id` et `equipe_id` sont `NULL`.
- *Compel* : Renseigne `peloton_id` (et `groupe_id` si spécifique au groupe).
- *Chef de Groupe* : Renseigne `peloton_id` et `groupe_id`.
- *Chef d'Équipe / Équipier* : Renseigne `peloton_id`, `groupe_id`, et `equipe_id`.

## 3. Gestion des Missions et Flexibilité

Le point le plus complexe de votre demande est la gestion des missions : pouvoir sélectionner une équipe, la modifier (ajouter des gens d'ailleurs), et changer le chef d'équipe *uniquement pour cette mission*, tout en gardant une trace exacte de qui a fait quoi.

Pour y parvenir, nous ne devons **pas** lier la mission uniquement à un `equipe_id`. Nous devons faire une **"photographie"** (un snapshot) de l'équipe au moment de la création de la mission.

### Table `missions`
- `id`
- `titre`, `description`, `date`, etc.
- `equipe_source_id` (Nullable) : Permet de se souvenir de quelle équipe on est parti à l'origine pour former le groupe de mission.

### Table Pivot `mission_user` (L'Affectation réelle)
C'est ici que la magie opère. Au lieu de dire "La mission X est assignée à l'équipe Y", on dit "La mission X est assignée à l'Utilisateur A (en tant que chef), Utilisateur B (membre), etc."
- `mission_id` (Clé étrangère vers `missions`)
- `user_id` (Clé étrangère vers `users`)
- `role_dans_mission` (enum: 'chef_mission', 'membre') : C'est ce champ qui permet de nommer quelqu'un d'autre comme chef pour *cette mission spécifique*, même s'il n'est pas le chef d'équipe habituel.

## 4. Workflow Pratique de Création de Mission

1. **Sélection de la base** : Dans l'interface, le Commandant sélectionne un "Peloton", puis un "Sous-groupe", puis une "Équipe".
2. **Chargement des membres** : L'interface charge automatiquement les membres de cette équipe. Le Chef d'équipe habituel est mis par défaut en "Chef de mission".
3. **Personnalisation (La flexibilité)** : 
   - L'utilisateur peut retirer un membre absent.
   - Il peut chercher un autre utilisateur (d'un autre sous-groupe ou peloton) et l'ajouter à la liste de cette mission.
   - Il peut attribuer le rôle de "Chef de mission" à une autre personne dans cette liste.
4. **Sauvegarde** : À la validation, la mission est créée, et on insère chaque personne présente dans la liste finale dans la table `mission_user`.

## 5. Avantages de cette solution
- **Traçabilité totale** : L'historique d'une mission pointera toujours vers les individus exacts qui y ont participé, même si l'équipe de base change de membres le mois suivant.
- **Flexibilité maximale** : On peut créer des missions "inter-équipes" ou "inter-pelotons" sans casser la structure de base.
- **Hiérarchie respectée** : L'organigramme (Peloton > Groupe > Équipe) reste intact et propre pour la gestion administrative quotidienne.
