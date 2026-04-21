@echo off
:: MissionFlow — Laravel Scheduler (Windows Task Scheduler)
:: Ajouter cette tâche dans le Planificateur de tâches Windows :
::   - Déclencheur : toutes les minutes
::   - Action : exécuter ce fichier .bat
::   - Démarrer dans : C:\wamp64\www\Missionflow

cd /d C:\wamp64\www\Missionflow
C:\wamp64\bin\php\php8.3.14\php.exe artisan schedule:run >> C:\wamp64\www\Missionflow\storage\logs\scheduler.log 2>&1
