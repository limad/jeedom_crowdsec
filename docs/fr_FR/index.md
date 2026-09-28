# Plugin CrowdSec

# Description

Ce plugin supervise une instance [CrowdSec](https://www.crowdsec.net/) via son API locale (LAPI). Il remonte le nombre de décisions actives, en distinguant les détections locales de la blocklist communautaire, ainsi que la liste et la dernière adresse IP bloquée localement.

La lecture utilise une clé *bouncer*, qui ne permet que de lire les décisions. Pour bannir et débannir des adresses IP, il faut en plus un compte *machine*.

# Prérequis

Sur la machine qui héberge CrowdSec, créer une clé bouncer :

```
sudo cscli bouncers add jeedom
```

Copier la clé affichée : elle n'est plus visible ensuite.

Optionnel, pour bannir / débannir depuis Jeedom, créer un compte machine :

```
sudo cscli machines add jeedom --password '<mot de passe>'
```

# Configuration de l'équipement

- **URL de la LAPI** : `http://127.0.0.1:8080` si CrowdSec tourne sur la box Jeedom. Si la LAPI est sur une autre machine, elle doit écouter sur une interface joignable (`api.server.listen_uri` dans `/etc/crowdsec/config.yaml`) ; préférer alors https, car la clé est envoyée à chaque requête.
- **Clé bouncer** : la clé créée ci-dessus (stockée chiffrée).
- **Auto-actualisation** : fréquence de lecture, par défaut toutes les 10 minutes.
- **Compte machine** / **Mot de passe machine** (optionnels) : le compte créé ci-dessus (mot de passe stocké chiffré). Sans compte machine, les commandes Bannir / Débannir échouent.
- **Durée de bannissement** : durée des bannissements faits depuis Jeedom, au format CrowdSec (`4h`, `30m`, `1h30m`...), par défaut `4h`.

Le bouton **Tester la connexion** vérifie que la LAPI répond et accepte la clé bouncer, puis le compte machine s'il est renseigné.

# Commandes

| Commande | Type | Description |
|---|---|---|
| Décisions actives | info numérique | Toutes les décisions actives |
| Décisions locales | info numérique | Décisions issues de la machine (détections, `cscli`...) |
| Décisions communautaires | info numérique | Décisions de la blocklist communautaire (CAPI) et des listes abonnées |
| IP bloquées localement | info texte | IP des décisions locales, la plus récente en premier |
| Dernière IP bloquée localement | info texte | IP de la décision locale la plus récente |
| Rafraîchir | action | Relit immédiatement les décisions |
| Bannir une IP | action message | Ajoute une décision `ban` locale sur l'IP saisie, pour la durée configurée (équivalent de `cscli decisions add --ip`) |
| Débannir une IP | action message | Supprime toutes les décisions actives sur l'IP saisie (équivalent de `cscli decisions delete --ip`) |
