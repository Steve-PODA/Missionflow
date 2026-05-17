# Plan d'Implémentation : Nouvelle Architecture et Missions

Ce document détaille les étapes techniques à suivre pas à pas pour implémenter la nouvelle architecture organisationnelle et le workflow de création de mission. Il inclut également la gestion des indisponibilités demandée.

## Étape 1 : Mise à jour de la Base de Données (Migrations)

1. **Création des nouvelles entités structurelles** :
   - Créer une migration pour la table `pelotons` (`id`, `nom`, `timestamps`).
   - Créer une migration pour la table `groupes` (`id`, `nom`, `peloton_id`, `timestamps`).
   - Créer une migration pour la table `equipes` (`id`, `nom`, `groupe_id`, `timestamps`).

2. **Modification de la table `users`** :
   - Ajouter/Mettre à jour la colonne `role` (enum ou string : commandant, adjoint, secretaire, compel, chef_groupe, chef_equipe, equipier).
   - Ajouter les clés étrangères `peloton_id`, `groupe_id`, et `equipe_id` (toutes `nullable`).
   - **Ajout de la disponibilité** : Ajouter une colonne `disponible` (boolean, par défaut `true`) ou `statut` pour gérer si le membre est apte à être déployé.

3. **Refonte de la gestion des missions** :
   - Modifier la table `missions` (ajouter un champ `equipe_source_id` nullable pour savoir quelle équipe a servi de base).
   - Créer la table pivot `mission_user` :
     - `mission_id`
     - `user_id`
     - `role_dans_mission` (ex: 'chef_mission', 'membre')
     - `timestamps`

## Étape 2 : Mise à jour des Modèles Eloquent (Relations)

1. **Modèle `Peloton`** : `hasMany` Groupes, `hasMany` Users.
2. **Modèle `Groupe`** : `belongsTo` Peloton, `hasMany` Equipes, `hasMany` Users.
3. **Modèle `Equipe`** : `belongsTo` Groupe, `hasMany` Users.
4. **Modèle `User`** : 
   - `belongsTo` Peloton, Groupe, Equipe.
   - `belongsToMany` Missions (via `mission_user` avec le pivot `role_dans_mission`).
5. **Modèle `Mission`** : 
   - `belongsToMany` Users (via `mission_user` avec le pivot `role_dans_mission`).

## Étape 3 : Seeders et Données de Test

- Créer un `DatabaseSeeder` générant l'arborescence (ex: 1 peloton > 2 groupes > 3 équipes par groupe).
- Assigner des utilisateurs avec les bons rôles dans ces différentes entités.
- **Test d'indisponibilité** : Marquer explicitement 2 ou 3 utilisateurs comme `disponible = false` dans la base de test.

## Étape 4 : Interface Utilisateur (Création de Mission & Personnalisation)

1. **Sélecteur en Cascade (Frontend)** :
   - Formulaire dynamique : Choix du Peloton -> Charge les Groupes -> Charge les Équipes.

2. **Personnalisation de l'équipe (Snapshot & Disponibilité)** :
   - Une fois l'équipe sélectionnée, afficher la liste des membres actuels.
   - **Fonctionnalité visuelle demandée** : Si un membre a `disponible == false`, sa ligne ou sa carte doit être visuellement **grisée** et son statut "Indisponible" doit être clairement affiché. On peut potentiellement bloquer sa sélection ou afficher un avertissement si on tente de forcer son ajout.
   - Permettre de **retirer** des membres (qu'ils soient grisés ou non).
   - Permettre de **changer le chef de mission** (via un sélecteur/radio bouton à côté des membres de la liste).
   - Ajouter un champ "Rechercher des renforts" pour piocher des utilisateurs d'autres équipes/pelotons et les ajouter à cette liste de mission.

## Étape 5 : Logique Backend (Sauvegarde de la Mission)

- Dans le `MissionController@store` :
  1. Valider les données (titre, lieu, dates).
  2. Créer l'entrée dans la table `missions`.
  3. Récupérer le tableau des participants modifiés depuis le front-end (qui contient les `user_id` finaux et leurs rôles de mission).
  4. Utiliser `$mission->users()->sync()` avec les données pivot pour enregistrer la "photographie" exacte de l'équipe de mission.

## Étape 6 : Vues et Tableaux de Bord (Consultation)

- Sur la page de détail de la mission, lire les membres depuis `$mission->users` (et non depuis l'équipe).
- Afficher clairement qui était le Chef de mission pour cette intervention spécifique en se basant sur le champ pivot.
