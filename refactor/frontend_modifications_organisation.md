# Plan des Modifications Frontend : Nouvelle Architecture Organisationnelle

Ce document liste les modifications nécessaires au niveau du frontend (Vue.js / Inertia) et des contrôleurs pour prendre en compte la nouvelle structure **Peloton > Groupe > Équipe** dans l'affichage et la gestion du personnel.

## 1. Affichage des Cartes du Personnel (Agent Cards)
Lors de l'affichage d'un agent (dans la liste du personnel ou lors de la sélection pour une mission), les informations de sa hiérarchie doivent être visibles.

**Modifications à apporter :**
- **UI (Vue.js) :** Ajouter des badges ou des champs textuels sur la carte de l'agent pour afficher :
  - **Nom du Peloton** (ex: *Peloton Alpha*)
  - **Nom du Groupe** (ex: *Groupe 1*)
  - **Numéro/Nom de l'Équipe** (ex: *Équipe A*)
- **Backend (Contrôleurs) :** S'assurer que la relation est chargée lors de l'envoi des données au frontend (Eager Loading).
  - Exemple : `User::with('team.group.peloton')->get()`

## 2. Création et Édition d'un Agent (Personnel)
Lors de l'assignation d'un agent à une unité, le formulaire doit refléter la hiérarchie stricte.

**Modifications à apporter :**
- **UI (Formulaires) :** Remplacer le champ d'assignation d'équipe simple par des **listes déroulantes en cascade** (Cascading Dropdowns) :
  1. **Sélection du Peloton** : Affiche la liste de tous les pelotons.
  2. **Sélection du Groupe** : Une fois le peloton choisi, affiche uniquement les groupes appartenant à ce peloton.
  3. **Sélection de l'Équipe** : Une fois le groupe choisi, affiche uniquement les équipes appartenant à ce groupe.
- **Backend :** Mettre à jour la validation (`FormRequest`) pour s'assurer que le `team_id` soumis existe bien.

## 3. Création et Édition d'une Mission (`MissionCreator`)
La sélection du personnel pour une mission doit être facilitée par la nouvelle structure.

**Modifications à apporter :**
- **UI (Cartes de sélection) :** Comme pour la liste générale, les cartes des agents dans le sélecteur de mission doivent afficher leur Peloton, Groupe et Équipe.
- **Filtres de recherche :** Ajouter des filtres permettant au créateur de la mission de trier les agents disponibles par :
  - Peloton
  - Groupe
  - Équipe
  *(Cela permet de déployer rapidement une équipe entière ou un groupe spécifique).*
- **Indicateur de disponibilité :** Continuer à griser les membres indisponibles, mais en gardant leurs informations d'unité visibles pour le contexte.

## 4. Affichage des Détails d'une Mission (Historique)
Puisque les membres d'une mission gardent une "photo" de leur équipe au moment de la mission (via la table pivot), l'affichage de la mission doit refléter ces données historiques, et non leur équipe actuelle.

**Modifications à apporter :**
- **UI (Détails Mission) :** Afficher l'équipe historique du membre lors de cette mission spécifique (données issues de `mission_user`), plutôt que son équipe actuelle.
- **Backend :** S'assurer que le contrôleur renvoie les données pivot lors de la consultation d'une mission (`$mission->users()->withPivot('team_name', 'group_name', 'peloton_name')`).
