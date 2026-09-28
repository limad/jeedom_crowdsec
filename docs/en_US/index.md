# CrowdSec plugin

# Description

This plugin monitors a [CrowdSec](https://www.crowdsec.net/) instance through its local API (LAPI). It reports the number of active decisions, separating local detections from the community blocklist, as well as the list and the last locally blocked IP address.

Reading uses a *bouncer* key, which can only read decisions. To ban and unban IP addresses, a *machine* account is also needed.

# Prerequisites

On the machine hosting CrowdSec, create a bouncer key:

```
sudo cscli bouncers add jeedom
```

Copy the displayed key: it cannot be shown again.

Optional, to ban / unban from Jeedom, create a machine account:

```
sudo cscli machines add jeedom --password '<password>'
```

# Equipment configuration

- **LAPI URL**: `http://127.0.0.1:8080` if CrowdSec runs on the Jeedom box. If the LAPI is on another machine, it must listen on a reachable interface (`api.server.listen_uri` in `/etc/crowdsec/config.yaml`); prefer https in that case, as the key is sent with every request.
- **Bouncer key**: the key created above (stored encrypted).
- **Auto-refresh**: reading frequency, every 10 minutes by default.
- **Machine account** / **Machine password** (optional): the account created above (password stored encrypted). Without a machine account, the Ban / Unban commands fail.
- **Ban duration**: duration of the bans made from Jeedom, in CrowdSec format (`4h`, `30m`, `1h30m`...), `4h` by default.

The **Test the connection** button checks that the LAPI answers and accepts the bouncer key, then the machine account if one is set.

# Commands

| Command | Type | Description |
|---|---|---|
| Active decisions | numeric info | All active decisions |
| Local decisions | numeric info | Decisions from the machine itself (detections, `cscli`...) |
| Community decisions | numeric info | Decisions from the community blocklist (CAPI) and subscribed lists |
| Locally blocked IPs | string info | IPs of the local decisions, most recent first |
| Last locally blocked IP | string info | IP of the most recent local decision |
| Refresh | action | Reads the decisions immediately |
| Ban an IP | message action | Adds a local `ban` decision on the entered IP, for the configured duration (same as `cscli decisions add --ip`) |
| Unban an IP | message action | Deletes every active decision on the entered IP (same as `cscli decisions delete --ip`) |
